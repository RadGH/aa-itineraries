<?php

/**
 * General tool used for syncing smartsheet data
 */
class Class_AH_Smartsheet_Sync {
	
	public function __construct() {
	}
	
	/**
	 * Gets a list of all posts of the given post type and its smartsheet name.
	 * Keys are the post ID, values are the smartsheet name.
	 * If no smartsheet name given the post is still included, but with an empty string as the name.
	 *
	 * @param string $post_type
	 *
	 * @return array
	 */
	public function get_post_list( $post_type ) {
		global $wpdb;
		
		$sql = <<<MySQL
SELECT DISTINCT p.ID as 'post_id', m.meta_value as 'smartsheet_id'

FROM {$wpdb->posts} p

LEFT JOIN {$wpdb->postmeta} m
ON p.ID = m.post_id AND m.meta_key = 'smartsheet_id'

WHERE
    p.post_type = %s
	AND
    p.post_status = 'publish'

LIMIT 2000;
MySQL;
		
		$sql = $wpdb->prepare( $sql, $post_type );
		
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		
		$post_list = array();
		
		if ( $rows ) foreach( $rows as $row ) {
			$post_list[ $row['post_id'] ] = $row['smartsheet_id'] ?: '';
		}
		
		return $post_list;
	}
	
	/**
	 * Get the post ID by the smartsheet name. The smartsheet name on the website must exactly match the one in the spreadsheet.
	 * If $post_list is provided (from get_post_list), that list will be checked instead.
	 *
	 * @param $smartsheet_name
	 * @param $post_type
	 * @param $post_list
	 *
	 * @return int|false
	 */
	public function get_post_id_from_smartsheet_id( $smartsheet_name, $post_type, $post_list = null ) {
		// Use post ID from the given list, if provided
		if ( $post_list !== null ) {
			$post_id = array_search( $smartsheet_name, $post_list );
			return $post_id ?: false;
		}
		
		// Search for the post ID directly
		global $wpdb;
		
		$sql = <<<MySQL
SELECT DISTINCT p.ID

FROM {$wpdb->posts} p

INNER JOIN {$wpdb->postmeta} m
ON p.ID = m.post_id AND m.meta_key = 'smartsheet_id'

WHERE
    p.post_type = %s
	AND
    p.post_status = 'publish'
    AND
    m.meta_value = %s

LIMIT 1;
MySQL;
		
		$sql = $wpdb->prepare( $sql, $post_type, $smartsheet_name );
		
		$post_id = (int) $wpdb->get_var( $sql );
		
		return $post_id ?: false;
	}
	
	/**
	 * Get an array of post IDs (as keys and values) which do not have matching smartsheet ID in the provided list.
	 * Posts without a smartsheet name are also included.
	 *
	 *
	 * @param array $post_list          Array of post IDs (keys = post ID, values = smartsheet_name)
	 * @param array $smartsheet_names   Array of strings  (keys = numeric, values = smartsheet_name)
	 *
	 * @return array
	 */
	/*
	public function get_unassigned_post_list( $post_list, $smartsheet_names ) {
		$unassigned_posts = array();
		
		if ( $post_list ) foreach( $post_list as $post_id => $name ) {
			// Check if post has no smartsheet name listed
			if ( ! $name ) {
				$unassigned_posts[$post_id] = $post_id;
			}
			
			// Check if post does not match a cell from the spreadsheet
			else if ( ! in_array( $name, $smartsheet_names, true ) ) {
				$unassigned_posts[$post_id] = $post_id;
			}
		}
		
		return $unassigned_posts;
	}
	*/
	
	/**
	 * Check if a cell from a spreadsheet is valid (not empty, not a formula error)
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public function is_cell_valid( $value ) {
		if ( empty($value) ) return false;
		if ( $value == '#INVALID OPERATION' ) return false;
		if ( $value == 'reserved' ) return false;
		return true;
	}
	
}