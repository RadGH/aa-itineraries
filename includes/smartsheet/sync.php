<?php

/**
 * General tool used for syncing smartsheet data
 */
class Class_AH_Smartsheet_Sync {
	
	public function __construct() {
		
		// Add options page for the "Sync Item" menu
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 20 );
		
		// Save the values from a "Sync Item" form
		add_action( 'init', array( $this, 'save_sync_item_data' ) );
		
	}
	
	/**
	 * Displays a "Sync ___" admin page but only when accessed with @see Class_AH_Smartsheet_Sync::get_sync_item_url()
	 *
	 * @return void
	 */
	public function register_admin_menus() {
		// Itineraries -> Sync Itinerary (Hidden unless accessed directly)
		$page = $_GET['page'] ?? false;
		if ( $page !== 'ah-sync-item' ) return;
	
		$post_id = $_GET['ah_post_id'] ?? false;
		
		$post_label = $post_id ? acf_get_post_type_label( get_post_type( $post_id ) ) : 'Item';
		
		add_submenu_page(
			'edit.php?post_type=ah_itinerary',
			'Sync ' . $post_label,
			'Sync ' . $post_label,
			'edit_pages',
			'ah-sync-item',
			array( $this, 'display_sync_item_page' )
		);
	}
	
	/**
	 * Get the link to sync a post with smartsheet
	 *
	 * @param $post_id
	 *
	 * @return string
	 */
	public function get_sync_item_url( $post_id ) {
		$url = admin_url('edit.php?post_type=ah_itinerary&page=ah-sync-item');
		
		return add_query_arg(array('ah_post_id' => $post_id), $url);
	}
	
	/**
	 * Save the values from a "Sync Item" form, depending on post type
	 *
	 * @return void
	 */
	public function save_sync_item_data() {
		if ( ! isset($_POST['action']) || $_POST['action'] !== 'ah_sync_item' ) return;
		
		$post_id = $_POST['ah']['post_id'] ?? false;
		$raw_values = $_POST['ah']['values'] ?? false;
		$raw_fields = $_POST['ah']['fields'] ?? false;
		
		$post_type = get_post_type( $post_id );
		$values = json_decode( stripslashes($raw_values), true );
		$fields_to_sync = stripslashes_deep($raw_fields);
		
		switch( $post_type ) {
			
			case 'ah_itinerary':
				AH_Smartsheet_Sync_Itineraries()->save_sync_item_data( $post_id, $values, $fields_to_sync );
				break;
			
			default:
				echo 'Unsupported post type "'. $post_type .'" in ' . __FILE__ . ':' . __LINE__;
				exit;
			
		}
		
		$url = get_edit_post_link( $post_id, 'raw' );
		$url = add_query_arg(array('ah_notice' => 'sync_item_success'), $url);
		wp_redirect( $url );
		exit;
	}
	
	/**
	 * Displays a page to preview item details before the item is synced
	 *
	 * @return void
	 */
	public function display_sync_item_page() {
		global $title;
		
		$post_id = $_GET['ah_post_id'] ?? false;
		$sheet_id = get_post_meta( $post_id, 'smartsheet_sheet_id', true );
		
		if ( ! $sheet_id ) {
			wp_die( 'Error: Spreadsheet not selected for post ID ' . $post_id );
			exit;
		}
		
		$edit_url = get_edit_post_link( $post_id );
		$post_title = get_the_title( $post_id );
		$post_link = ah_create_html_link( $edit_url, $post_title, false );
		
		?>
		<div class="wrap">
			
			<h1><?php echo $title; ?>: <?php echo $post_link; ?></h1>
		
			<div id="poststuff" class="poststuff">
				<?php
				AH_Smartsheet_Sync_Itineraries()->display_sync_results_page( $post_id, $sheet_id );
				?>
			</div>
			
		</div>
		<?php
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
	 * Get the post ID by the smartsheet ID. The smartsheet ID on the website must exactly match the one in the spreadsheet.
	 * If $post_list is provided (from get_post_list), that list will be checked instead.
	 *
	 * @param $smartsheet_id
	 * @param $post_type
	 * @param $post_list
	 *
	 * @return int|false
	 */
	public function get_post_id_from_smartsheet_id( $smartsheet_id, $post_type, $post_list = null ) {
		// Use post ID from the given list, if provided
		if ( $post_list !== null ) {
			$post_id = array_search( $smartsheet_id, $post_list );
			
			// old: Hotel Schonegg | Wengen
			// new: Schonegg - Wengen
			
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
		
		$sql = $wpdb->prepare( $sql, $post_type, $smartsheet_id );
		
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
		if ( empty( str_replace('-', '', $value) ) ) return false; // is the cell is only hyphens?
		return true;
	}
	
	/**
	 * Gets an array including "name" and "id" from a list of rows provided by Smartsheet.
	 * Used for both villages and hotels.
	 *
	 * @param array[] $rows            Rows from: AH_Smartsheet_API()->get_rows_from_sheet( $sheet_id )
	 * @param string $required_column  The column that is required to have a value, else the row is skipped
	 * @param array $columns           Array of column IDs. Keys are used for each item that is returned.
	 *
	 * @return array
	 */
	public function get_values_from_rows( $rows, $required_column, $columns ) {
		$items = array();
		
		// Loop through each row
		if ( $rows ) foreach( $rows as $row ) {
			
			$item = array();
			
			// Get each specific column's value from this row
			foreach( $columns as $key => $column_id ) {
				$cell = ah_find_in_array( $row['cells'], 'columnId', $column_id );
				$value = $cell['value'] ?? false;
				
				if ( $key === $required_column && ! AH_Smartsheet_Sync()->is_cell_valid($value) ) {
					// Required column is invalid, skip this row
					continue 2;
				}
				
				$item[ $key ] = $cell['value'] ?? false;
			}
			
			// Store the item
			$items[] = $item;
		}
		
		return $items;
	}
	
}