<?php

class Class_AH_Smartsheet_Sync_Hikes {
	
	public $columns = array(
		'hike_id'   => 'Hike ID',
		'hike_name' => 'Hike Name',
		'region'    => 'Region',
		'url'       => 'URL',
	);
	
	public $hike_list = array();
	
	public function __construct() {
		
		// Register ACF settings pages
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 25 );
		
		// Save settings page
		add_action( 'admin_init', array( $this, 'save_admin_menu_settings' ) );
		
		// Sync hikes button from the settings page
		add_action( 'admin_init', array( $this, 'process_hike_sync' ) );
		
		// Create a hike from a link in the settings page
		add_action( 'admin_init', array( $this, 'create_hike_from_link' ) );
		
		// Sync a hike from a link in the settings page
		add_action( 'admin_init', array( $this, 'sync_hike_from_link' ) );
		
	}
	
	public function register_admin_menus() {
		if ( function_exists('acf_add_options_page') ) {
			// Smartsheet Settings -> Sync Hikes
			// NOTE: Must be defined by ACF first, then override with a WP submenu page
			acf_add_options_sub_page( array(
				'parent_slug' => 'acf-ah-settings-parent',
				'page_title'  => 'Sync Hikes',
				'menu_title'  => 'Sync Hikes',
				'capability' => 'manage_options',
				'menu_slug'   => 'ah-smartsheet-hikes',
			) );
			add_submenu_page(
				null,
				'Sync Hikes',
				'Sync Hikes',
				'manage_options',
				'ah-smartsheet-hikes',
				array( $this, 'display_admin_page' )
			);
			
		}
	}
	
	public function display_admin_page() {
		include( AH_PATH . '/templates/admin/smartsheet-hikes.php' );
	}
	
	public function save_admin_menu_settings() {
		$action = $_POST['ah-action'] ?? false;
		if ( ! wp_verify_nonce( $action, 'save-hike-info' ) ) return;
		
		$data = stripslashes_deep($_POST['ah']);
		
		// Sheet ID
		$sheet_id = $data['sheet_id'];
		update_option( 'ah_hike_sheet_id', $sheet_id, false );
		
		// Column IDs
		$column_ids = $this->format_column_ids( $data['column_ids'] );
		update_option( 'ah_hike_column_ids', $column_ids, false );
		
		// Reload the form (to clear post data from browser history)
		wp_redirect(add_query_arg(array('ah_notice' => 'hike_list_updated')));
		exit;
	}
	
	public function get_sheet_id() {
		return get_option( 'ah_hike_sheet_id' );
	}
	
	public function get_column_ids() {
		$column_ids = get_option( 'ah_hike_column_ids' );
		
		$column_ids = $this->format_column_ids( $column_ids );
		
		return $column_ids;
	}
	
	/**
	 * Takes column ID data (example: from options or $_POST) and returns a pre-formatted array with those values
	 * Any extra columns provided are discarded.
	 * Any missing columns are provided and set to null.
	 *
	 * @param array $column_data
	 *
	 * @return array {
	 *      @type string|null $hike_id
	 *      @type string|null $hike_name
	 *      @type string|null $region
	 *      @type string|null $url
	 * }
	 */
	public function format_column_ids( $column_data ) {
		$column_ids = array();
		
		foreach( $this->columns as $key => $title ) {
			$column_ids[$key] = null;
		}
		
		$column_ids = shortcode_atts( $column_ids, $column_data );
		
		return $column_ids;
	}
	
	/**
	 * Get list of stored hike data which came from Smartsheet
	 *
	 * @return array {
	 *      @type string $smartsheet_id
	 *      @type string $smartsheet_name
	 *      @type string $region
	 *      @type string $url
	 * }
	 */
	public function get_stored_hike_list() {
		$hike_list = get_option( 'ah_hike_list' );
		if ( empty($hike_list) ) $hike_list = array();
		
		return $hike_list;
	}
	
	/**
	 * Separates hikes which are attached to posts, and those that aren't.
	 *
	 * Returns an array:
	 *     [0] = items that exist on Smartsheet + WordPress
	 *     [1] = items missing from WordPress
	 *     [2] = items missing from Smartsheet
	 *
	 * @param array[] $item_list   array of items which each include "id" and "name"
	 *
	 * @return array {
	 *      [0] [1] [2] = array {
	 *           @type string|null $smartsheet_name
	 *           @type string|null $smartsheet_id
	 *           @type int|null    $post_id
	 *      }
	 * }
	 */
	public function group_by_smartsheet_assignment( $item_list ) {
		$assigned = array();
		$unassigned = array();
		$found_ids = array();
		
		// Check each hike by name to see if it is assigned to a post
		foreach( $item_list as $item ) {
			$smartsheet_name = $item['smartsheet_name'];
			$smartsheet_id = $item['smartsheet_id'];
			
			$post_id = $this->get_hike_by_smartsheet_id( $smartsheet_id );
			
			if ( $post_id ) {
				$found_ids[ $post_id ] = $post_id;
			}
			
			$item = array(
				'smartsheet_name' => $smartsheet_name,
				'smartsheet_id' => $smartsheet_id,
				'post_id' => $post_id,
			);
			
			if ( $post_id ) {
				$assigned[] = $item;
			}else{
				$unassigned[] = $item;
			}
		}
		
		// Identify hikes that only exist in WordPress as "missing" from smartsheet
		$missing = array();
		$remaining_ids = $this->get_all_item_ids( $found_ids );
		
		if ( $remaining_ids ) foreach( $remaining_ids as $id ) {
			$missing[] = array(
				'smartsheet_name' => false,
				'smartsheet_id' => false,
				'post_id' => $id,
			);
		}
		
		// Return all arrays in a format compatible with list()
		return array( $assigned, $unassigned, $missing );
	}
	
	public function get_all_item_ids( $exclude_ids = array() ) {
		$post_type = AH_Hike()->get_post_type();
		
		$args = array(
			'post_type' => $post_type,
			'nopaging' => true,
			'fields' => 'ids',
			'post__not_in' => $exclude_ids,
		);
		
		return get_posts($args);
	}
	
	/**
	 * Get sheet data which came from Smartsheet
	 *
	 * @return false|array {
	 *      @type int $sheet_id
	 *      @type string $name
	 *      @type string $permalink
	 * }
	 */
	public function get_stored_sheet_data() {
		$sheet_data = get_option( 'ah_hike_sheet' );
		if ( empty($sheet_data) ) $sheet_data = false;
		
		return $sheet_data;
	}
	
	/**
	 * Return the URL to edit a sheet in Smartsheet
	 *
	 * @return string|false
	 */
	public function get_smartsheet_permalink() {
		$data = $this->get_stored_sheet_data();
		return $data['permalink'] ?? false;
	}
	
	/**
	 * Get a list of hike names from the master hike spreadsheet
	 *
	 * @return array|false
	 */
	public function sync_hike_from_smartsheet() {
		// Get the sheet ID
		$sheet_id = $this->get_sheet_id();
		if ( ! $sheet_id ) return false;
		
		// Get column IDs to use for structure
		$column_ids = $this->get_column_ids();
		if ( ! $column_ids ) return false;
		
		// Get the sheet details
		$sheet = AH_Smartsheet_API()->get_sheet_by_id( $sheet_id );
		if ( ! $sheet ) return false;
		
		// Store information about the sheet itself
		$sheet_data = array(
			// @todo sheet smartsheet_id
			'sheet_id' => $sheet['id'], // 2463217603268484
			'sheet_name' => $sheet['name'], // "Copy of Master List - Hikes/Maps"
			'permalink' => $sheet['permalink'], // "https://app.smartsheet.com/sheets/866hH6jQCxmvF2f7w4cv3c2rPh9XXvgxVmmVX8W1?view=grid"
		);
		
		update_option( 'ah_hike_sheet', $sheet_data, false );
		
		// Get rows from the sheet
		$rows = AH_Smartsheet_API()->get_rows_from_sheet( $sheet_id );
		if ( ! $rows ) return false;
		
		// Get a list of hikes
		$hike_list = $this->get_values_from_rows( $rows, array(
			'smartsheet_id'   => $column_ids['hike_id'],
			'smartsheet_name' => $column_ids['hike_name'],
			
			'region'    => $column_ids['region'],
			'url'       => $column_ids['url'],
		) );
		
		// Save the hike list
		if ( $hike_list ) {
			update_option( 'ah_hike_list', $hike_list, false );
		}
	
		// Save the last sync date
		update_option( 'ah_hike_last_sync', current_time('Y-m-d H:i:s'), false );
		
		return $hike_list;
	}
	
	/**
	 * Gets an array including "name" and "id" from a list of rows provided by Smartsheet.
	 * Used for both Hikes.
	 *
	 * @param array[] $rows         Rows from: AH_Smartsheet_API()->get_rows_from_sheet( $sheet_id )
	 * @param array $columns        Array of column IDs where keys are the name to be returned.
	 *                              Note: 'smartsheet_id' and 'smartsheet_name' columns are required.
	 *
	 * @return array
	 */
	public function get_values_from_rows( $rows, $columns ) {
		$items = array();
		
		// Loop through each row
		if ( $rows ) foreach( $rows as $row ) {
			
			$item = array();
			
			// Get each specific column's value from this row
			foreach( $columns as $key => $column_id ) {
				$cell = ah_find_in_array( $row['cells'], 'columnId', $column_id );
				$item[ $key ] = $cell['value'] ?? false;
			}
			
			// Check that name and id are valid
			if (
				AH_Smartsheet_Sync()->is_cell_valid( $item['hike_id'] )
				&&
				AH_Smartsheet_Sync()->is_cell_valid( $item['hike_name'] )
			) {
				$items[ $item['smartsheet_id'] ] = $item;
			}
		}
		
		// Sort by item name
		if ( $items ) $items = ah_sort_by_key( $items, 'hike_name' );
		
		return $items;
	}
	
	/**
	 * When visiting the link to sync from the Sync Hikes page, triggers the sync and does a redirect when successful
	 *
	 * @return void
	 */
	public function process_hike_sync() {
		if ( ! isset($_GET['ah_sync_hikes']) ) return;
		
		$url = remove_query_arg('ah_sync_hikes');
		
		// Perform the sync with Smartsheet's API
		$result = $this->sync_hike_from_smartsheet();
		
		if ( $result === false ) {
			// The sync did not complete
			ah_add_alert( 'error', 'Hike Sync Failed', 'Syncing hikes from smartsheet did not complete successfully. The previously stored hike information will be preserved.' );
			$url = add_query_arg(array('ah_notice' => 'sync_hikes_failed'), $url);
			wp_redirect($url);
			exit;
		}
		
		// Data to send in the URL, used in the notice popup
		$data = array(
			'hikes' => count($result),
		);
		
		// Build URL to redirect to
		$url = add_query_arg(array(
			'ah_notice' => 'sync_hikes_success',
			'ah_notice_data' => urlencode(json_encode($data))
		), $url);
		
		wp_redirect($url);
		exit;
	}
	
	/**
	 * Gets a list of all posts of the given post type and its smartsheet name.
	 * Keys are the post ID, values are the smartsheet name.
	 * If no smartsheet name given the post is still included, but with an empty string as the name.
	 *
	 * @see Class_AH_Smartsheet_Sync::get_post_list()
	 *
	 * @return string[] {
	 *     @type int $key
	 *     @type string $value
	 * }
	 */
	public function preload_hike_post_list() {
		if ( $this->hike_list === null ) {
			$this->hike_list = AH_Smartsheet_Sync()->get_post_list( AH_Hike()->get_post_type() );
		}
		
		return $this->hike_list;
	}
	
	/**
	 * Get the post ID of a hike by smartsheet name.
	 * If a list of hikes was preloaded, finds the post in that list instead of doing an individual query.
	 *
	 * @see Class_AH_Smartsheet_Sync_Hikes::preload_hike_post_list()
	 * @see Class_AH_Smartsheet_Sync::get_post_list()
	 *
	 * @param $smartsheet_id
	 *
	 * @return int|false
	 */
	public function get_hike_by_smartsheet_id( $smartsheet_id ) {
		return AH_Smartsheet_Sync()->get_post_id_from_smartsheet_id( $smartsheet_id, AH_Hike()->get_post_type(), $this->hike_list );
	}
	
	/**
	 * Get a link that will automatically create a hike based on smartsheet name/id
	 *
	 * @param string $smartsheet_id    id of the item, "Gasterlal - CH"
	 *
	 * @return string
	 */
	public function get_edit_hike_link( $smartsheet_id ) {
		$base_url = add_query_arg(array('page' => $_GET['page'] ?? ''), admin_url('admin.php'));
		
		$args = array(
			'ah_create_item' => 'hike',
			'ah_smartsheet_id' => $smartsheet_id,
		);
		
		$url = add_query_arg($args, $base_url);
		
		return $url;
	}
	
	/**
	 * Create a hike from a row in the spreadsheet (within the "Sync Hikes" settings screen)
	 *
	 * @return void
	 */
	public function create_hike_from_link() {
		if ( ! isset($_GET['ah_create_item']) ) return;
		
		$type = stripslashes($_GET['ah_create_item']);
		if ( $type != 'hike' ) return;
		
		$smartsheet_id = stripslashes($_GET['ah_smartsheet_id']);
		
		$post_type = AH_Hike()->get_post_type();
		$existing_post_id = $this->get_hike_by_smartsheet_id($smartsheet_id);
		
		// If it already exists, show an error message with link to edit
		if ( $existing_post_id ) {
			$message = sprintf(
				'Hike already exists: <a href="%s">%s #%d</a>',
				esc_attr(get_edit_post_link($existing_post_id)),
				esc_html($smartsheet_id),
				esc_html($existing_post_id)
			);
			wp_die($message);
			exit;
		}
		
		// Get the Smartsheet data from the list
		$item_data = $this->get_stored_item( $smartsheet_id );
		
		if ( ! $item_data ) {
			wp_die('Cannot create hike: item data not found with smartsheet id "'. esc_html($smartsheet_id) .'"');
			exit;
		}
		
		// Smartsheet ID is used as the post title
		$post_title = $smartsheet_id;
		
		// Create the post
		$args = array(
			'post_type' => $post_type,
			'post_title' => $post_title,
			'post_status' => 'publish',
		);
		
		$post_id = wp_insert_post( $args );
		
		if ( ! $post_id || is_wp_error( $post_id ) ) {
			wp_die( 'Failed to insert hike, wp_insert_post returned an error.', 'Error', $post_id );
			exit;
		}
		
		// Sync the item with data from Smartsheet
		$this->sync_item( $post_id, $item_data );
		
		// Redirect to the edit post screen
		$url = get_edit_post_link( $post_id, 'raw' );
		
		// Add a message to indicate the post was created successfully
		$url = add_query_arg(array('ah_notice' => 'smartsheet_hike_inserted'), $url);
		
		wp_redirect( $url );
		exit;
	}
	
	/**
	 * Get a link that will automatically create a hike based on smartsheet name/id
	 *
	 * @param string $post_id               ID of the post in WordPress
	 * @param string|null $smartsheet_id    Optional. The title which should match the "WordPress ID" column in Smartsheet.
	 *
	 * @return string
	 */
	public function get_sync_hike_link( $post_id, $smartsheet_id = null ) {
		$base_url = isset($_GET['page']) ? add_query_arg(array('page' => $_GET['page'] ?? '')) : $_SERVER['REQUEST_URI'];
		
		if ( $smartsheet_id === null ) {
			$smartsheet_id = get_post_meta( $post_id, 'smartsheet_id', true );
			if ( ! $smartsheet_id ) return false;
		}
		
		$args = array(
			'ah_sync_item' => 'hike',
			'ah_post_id' => $post_id,
			'ah_smartsheet_id' => $smartsheet_id,
		);
		
		$url = add_query_arg($args, $base_url);
		
		return $url;
	}
	
	/**
	 * Returns the item with matching smartsheet ID from the stored hike list.
	 * False if not found.
	 *
	 * @param string $smartsheet_id
	 *
	 * @return array|false
	 */
	public function get_stored_item( $smartsheet_id ) {
		$list = $this->get_stored_hike_list();
		
		foreach( $list as $item ) {
			if ( $item['smartsheet_id'] == $smartsheet_id ) {
				return $item;
			}
		}
		
		return false;
	}
	
	/**
	 * Sync a hike from a row in the spreadsheet (within the "Sync Hikes" settings screen)
	 *
	 * @return void
	 */
	public function sync_hike_from_link() {
		if ( ! isset($_GET['ah_sync_item']) ) return;
		
		$type = stripslashes($_GET['ah_sync_item']);
		if ( $type != 'hike' ) return;
		
		$post_id = stripslashes($_GET['ah_post_id']);
		$smartsheet_id = stripslashes($_GET['ah_smartsheet_id']);
		
		// Look for the corresponding item to sync with
		$item_data = $this->get_stored_item( $smartsheet_id );
		
		if ( ! $item_data ) {
			wp_die('Cannot sync hike: item data not found with smartsheet id "'. esc_html($smartsheet_id) .'"' );
			exit;
		}
		
		// Update the item
		$this->sync_item( $post_id, $item_data );
		
		// Redirect when complete
		$url = get_edit_post_link( $post_id, 'raw' );
		
		// Add a message to indicate the post was created successfully
		$url = add_query_arg(array('ah_notice' => 'smartsheet_hike_sync_complete'), $url);
		
		wp_redirect( $url );
		exit;
	}
	
	/**
	 * Sync a hike using data stored from a previous Smartsheet sync.
	 * $data must include an "id" property (smartsheet id) or else returns false without updating the item.
	 *
	 * Does not perform any API queries.
	 *
	 * @param int    $post_id  post ID of a hike
	 * @param array  $data     {
	 *     @type string $hike_id   "Schwarzwaldalp to Grindelwald - BO"
	 *     @type string $hike_name "Schwarzwaldalp to Grindelwald"
	 *     @type string $region    "BO"
	 *     @type string $url       "https://www.outdooractive.com/en/r/64809261"
	 * }
	 *
	 * @return bool
	 */
	public function sync_item( $post_id, $data ) {
		$smartsheet_id = $data['smartsheet_id'];
		$smartsheet_name = $data['smartsheet_name'];
		if ( ! $smartsheet_id ) return false;
		
		// Smartsheet id
		update_post_meta( $post_id, 'smartsheet_id', $smartsheet_id );
		update_post_meta( $post_id, 'smartsheet_name', $smartsheet_name );
		
		// Last sync date
		update_post_meta( $post_id, 'smartsheet_last_sync', current_time('Y-m-d H:i:s') );
		
		// Hike fields
		update_post_meta( $post_id, 'hike_name', $data['hike_name'] ?? '' );
		update_post_meta( $post_id, 'region', $data['region'] ?? '' );
		update_post_meta( $post_id, 'url', $data['url'] ?? '' );
		
		return true;
	}
	
}
