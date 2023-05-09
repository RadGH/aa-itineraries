<?php

class Class_AH_Smartsheet_Sync_Hotels_And_Villages {
	
	public $columns = array(
		'hotel_name'      => 'Hotel Name',
		'hotel_id'        => 'Hotel ID',
		'village_name'    => 'Village Name',
		'village_id'      => 'Village ID',
		// Each hotel item also includes "smartsheet_row_id" which is not displayed
	);
	
	public $hotel_list = null;
	public $village_list = null;
	
	public function __construct() {
		
		// Register ACF settings pages
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 25 );
		
		// Save settings page
		add_action( 'admin_init', array( $this, 'save_admin_menu_settings' ) );
		
		// Sync hotels button from the settings page
		add_action( 'admin_init', array( $this, 'process_hotel_info_sync' ) );
		
		// Create a village or hotel from a link in the settings page
		add_action( 'admin_init', array( $this, 'create_village_or_hotel_from_link' ) );
		
	}
	
	public function register_admin_menus() {
		if ( function_exists('acf_add_options_page') ) {
			// Smartsheet Settings -> Sync Villages and Hotels
			// NOTE: Must be defined by ACF first, then override with a WP submenu page
			acf_add_options_sub_page( array(
				'parent_slug' => 'acf-ah-settings-parent',
				'page_title'  => 'Sync Villages and Hotels',
				'menu_title'  => 'Sync Villages and Hotels',
				'capability' => 'manage_options',
				'menu_slug'   => 'ah-smartsheet-villages-and-hotels',
			) );
			add_submenu_page(
				null,
				'Sync Villages and Hotels',
				'Sync Villages and Hotels',
				'manage_options',
				'ah-smartsheet-villages-and-hotels',
				array( $this, 'display_admin_page' )
			);
			
		}
	}
	
	public function display_admin_page() {
		include( AH_PATH . '/templates/admin/smartsheet-villages-and-hotels.php' );
	}
	
	public function save_admin_menu_settings() {
		$action = $_POST['ah-action'] ?? false;
		if ( ! wp_verify_nonce( $action, 'save-hotel-info' ) ) return;
		
		$data = stripslashes_deep($_POST['ah']);
		
		// Sheet ID
		$sheet_id = $data['sheet_id'];
		update_option( 'ah_hotel_info_sheet_id', $sheet_id, false );
		
		// Column IDs
		$column_ids = $this->format_column_ids( $data['column_ids'] );
		update_option( 'ah_hotel_info_column_ids', $column_ids, false );
		
		// Reload the form (to clear post data from browser history)
		wp_redirect(add_query_arg(array('ah_notice' => 'hotel_list_updated')));
		exit;
	}
	
	public function get_sheet_id() {
		return get_option( 'ah_hotel_info_sheet_id' );
	}
	
	public function get_column_ids() {
		$column_ids = get_option( 'ah_hotel_info_column_ids' );
		
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
	 *      @type string|null $hotel_name
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
	 * Get list of stored hotel data which came from Smartsheet
	 *
	 * @return array {
	 *      @type string $name
	 *      @type string $id
	 * }
	 */
	public function get_stored_hotel_list() {
		$hotel_list = get_option( 'ah_hotel_list' );
		if ( empty($hotel_list) ) $hotel_list = array();
		
		return $hotel_list;
	}
	
	/**
	 * Get an array of villages used by the hotel list. Returns just the village names.
	 *
	 * @return array {
	 *      @type string $name
	 *      @type string $id
	 * }
	 */
	public function get_stored_village_list() {
		$village_list = get_option( 'ah_village_list' );
		if ( empty($village_list) ) $village_list = array();
		
		return $village_list;
	}
	
	/**
	 * Separates hotels which are attached to posts, and those that aren't.
	 * Returns an array where [0] is assigned items, and [1] is unassigned items.
	 *
	 * @param array[] $item_list   array of items which each include "id" and "name"
	 * @param string   $type       either "hotel" or "village"
	 *
	 * @return array {
	 *      [0] and [1] = array {
	 *           @type string $smartsheet_name
	 *           @type string $smartsheet_id
	 *           @type int    $post_id
	 *      }
	 * }
	 */
	public function group_by_smartsheet_assignment( $item_list, $type ) {
		$assigned = array();
		$unassigned = array();
		
		// Check each hotel by name to see if it is assigned to a post
		foreach( $item_list as $item ) {
			$smartsheet_name = $item['name'];
			$smartsheet_id = $item['id'];
			
			if ( $type == 'hotel' ) {
				$post_id = $this->get_hotel_by_smartsheet_id( $smartsheet_id );
			}else{
				$post_id = $this->get_village_by_smartsheet_id( $smartsheet_id );
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
		
		// Return both arrays
		return array( $assigned, $unassigned );
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
		$sheet_data = get_option( 'ah_hotel_sheet' );
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
	 * Get a list of hotel and village names from the master hotel spreadsheet
	 *
	 * @return array|false
	 */
	public function sync_hotel_info_from_smartsheet() {
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
			'sheet_id' => $sheet['id'], // 7567715780061060
			'name' => $sheet['name'], // "Copy of Master List - Hotel Info"
			'permalink' => $sheet['permalink'], // "https://app.smartsheet.com/sheets/FXq9cXvg56pv22JCpVCPC69mW2j7jf29PHRr7x31"
		);
		
		update_option( 'ah_hotel_sheet', $sheet_data, false );
		
		// Get rows from the sheet
		$rows = AH_Smartsheet_API()->get_rows_from_sheet( $sheet_id );
		if ( ! $rows ) return false;
		
		// 1. Get a list of hotels
		$hotel_list = $this->get_values_from_rows( $rows, $column_ids['hotel_name'], $column_ids['hotel_id'] );
		
		// Save the hotel list
		if ( $hotel_list ) {
			update_option( 'ah_hotel_list', $hotel_list, false );
		}
	
		// 2. Get a list of villages
		$village_list = $this->get_values_from_rows( $rows, $column_ids['village_name'], $column_ids['village_id'] );
		
		// Save the hotel list
		if ( $village_list ) {
			update_option( 'ah_village_list', $village_list, false );
		}
		
		// Save the last sync date
		update_option( 'ah_hotels_and_villages_last_sync', current_time('Y-m-d H:i:s'), false );
		
		return array( 'hotel_list' => $hotel_list, 'village_list' => $village_list );
	}
	
	/**
	 * Gets an array including "name" and "id" from a list of rows provided by Smartsheet.
	 * Used for both villages and hotels.
	 *
	 * @param array[] $rows         Rows from: AH_Smartsheet_API()->get_rows_from_sheet( $sheet_id )
	 * @param string $name_column   Column ID for the item name
	 * @param string $id_column     Column ID for the item ID
	 *
	 * @return array[]
	 */
	public function get_values_from_rows( $rows, $name_column, $id_column ) {
		$items = array();
		
		// Loop through each row
		if ( $rows ) foreach( $rows as $row ) {
			
			// Get item name and id from their respective cells
			$name_cell = ah_find_in_array( $row['cells'], 'columnId', $name_column );
			$id_cell = ah_find_in_array( $row['cells'], 'columnId', $id_column );
			
			$item_name = $name_cell['value'] ?? false;
			$item_id = $id_cell['value'] ?? false;
			
			// If the name and ID are valid, add the item
			if ( AH_Smartsheet_Sync()->is_cell_valid( $item_name ) && AH_Smartsheet_Sync()->is_cell_valid( $item_id ) ) {
				$items[ $item_id ] = array(
					'name' => $item_name,
					'id' => $item_id,
				);
			}
		}
		
		// Sort by item name
		if ( $items ) $items = ah_sort_by_key( $items, 'name' );
		
		return $items;
	}
	
	/**
	 * When visiting the link to sync from the Sync Villages and Hotels page, triggers the sync and does a redirect when successful
	 *
	 * @return void
	 */
	public function process_hotel_info_sync() {
		if ( ! isset($_GET['ah_sync_hotels_and_villages']) ) return;
		
		$url = remove_query_arg('ah_sync_hotels_and_villages');
		
		// Perform the sync with Smartsheet's API
		$result = $this->sync_hotel_info_from_smartsheet();
		
		if ( $result === false ) {
			// The sync did not complete
			ah_add_alert( 'error', 'Hotel and Village Sync Failed', 'Syncing hotel information from smartsheet did not complete successfully. The previously stored hotel and village information will be preserved.' );
			$url = add_query_arg(array('ah_notice' => 'sync_hotels_failed'), $url);
			wp_redirect($url);
			exit;
		}
		
		// Data to send in the URL, used in the notice popup
		$data = array(
			'hotels' => count($result['hotel_list']),
			'villages' => count($result['village_list']),
		);
		
		// Build URL to redirect to
		$url = add_query_arg(array(
			'ah_notice' => 'sync_hotels_success',
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
	public function preload_hotel_post_list() {
		if ( $this->hotel_list === null ) {
			$this->hotel_list = AH_Smartsheet_Sync()->get_post_list( AH_Hotel()->get_post_type() );
		}
		
		return $this->hotel_list;
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
	public function preload_village_post_list() {
		if ( $this->village_list === null ) {
			$this->village_list = AH_Smartsheet_Sync()->get_post_list( AH_Village()->get_post_type() );
		}
		
		return $this->village_list;
	}
	
	/**
	 * Get the post ID of a hotel by smartsheet name.
	 * If a list of hotels was preloaded, finds the post in that list instead of doing an individual query.
	 *
	 * @see Class_AH_Smartsheet_Sync_Hotels_And_Villages::preload_hotel_post_list()
	 * @see Class_AH_Smartsheet_Sync::get_post_list()
	 *
	 * @param $smartsheet_id
	 *
	 * @return int|false
	 */
	public function get_hotel_by_smartsheet_id( $smartsheet_id ) {
		return AH_Smartsheet_Sync()->get_post_id_from_smartsheet_id( $smartsheet_id, AH_Hotel()->get_post_type(), $this->hotel_list );
	}
	
	/**
	 * Get the post ID of a village by smartsheet name.
	 * If a list of villages was preloaded, finds the post in that list instead of doing an individual query.
	 *
	 * @see Class_AH_Smartsheet_Sync_Hotels_And_Villages::preload_village_post_list()
	 * @see Class_AH_Smartsheet_Sync::get_post_list()
	 *
	 * @param $smartsheet_id
	 *
	 * @return int|false
	 */
	public function get_village_by_smartsheet_id( $smartsheet_id ) {
		return AH_Smartsheet_Sync()->get_post_id_from_smartsheet_id( $smartsheet_id, AH_Village()->get_post_type(), $this->village_list );
	}
	
	/**
	 * Get a link that will automatically create a village or hotel based on smartsheet name/id
	 *
	 * @param string $type             either "village" or "hotel"
	 * @param string $smartsheet_name  name of the item, "Gasternal"
	 * @param string $smartsheet_id    id of the item, "Gasterlal - CH"
	 *
	 * @return string
	 */
	public function get_edit_village_or_hotel_link( $type, $smartsheet_name, $smartsheet_id ) {
		$base_url = add_query_arg(array('page' => $_GET['page'] ?? ''), admin_url('admin.php'));
		
		$args = array(
			'ah_create_item' => $type,
			'ah_post_title' => $smartsheet_name,
			'ah_smartsheet_id' => $smartsheet_id,
		);
		
		$url = add_query_arg($args, $base_url);
		
		return $url;
	}
	
	/**
	 * Create a hotel from a row in the spreadsheet (within the "Sync Villages and Hotels" settings screen)
	 *
	 * @return void
	 */
	public function create_village_or_hotel_from_link() {
		if ( ! isset($_GET['ah_create_item']) ) return;
		
		$type = stripslashes($_GET['ah_create_item']);
		$post_title = stripslashes($_GET['ah_post_title']);
		$smartsheet_id = stripslashes($_GET['ah_smartsheet_id']);
		
		if ( $type == 'village' ) {
			$type_name = 'Village';
			$post_type = AH_Village()->get_post_type();
			$existing_post_id = $this->get_village_by_smartsheet_id($smartsheet_id);
		}else if ( isset($_GET['ah_create_hotel']) ) {
			$type_name = 'Hotel';
			$post_type = AH_Hotel()->get_post_type();
			$existing_post_id = $this->get_hotel_by_smartsheet_id($smartsheet_id);
		}else{
			return;
		}
		
		// If it already exists, show an error message with link to edit
		if ( $existing_post_id ) {
			$message = sprintf(
				'%s already exists: <a href="%s">%s #%d</a>',
				esc_html($type_name),
				esc_attr(get_edit_post_link($existing_post_id)),
				esc_html($post_title),
				esc_html($existing_post_id)
			);
			wp_die($message);
			exit;
		}
		
		// Create the post
		$args = array(
			'post_type' => $post_type,
			'post_title' => $post_title,
			'post_status' => 'publish',
		);
		
		$post_id = wp_insert_post( $args );
		
		if ( ! $post_id || is_wp_error( $post_id ) ) {
			wp_die( 'Failed to insert ' . $type_name . ', wp_insert_post returned an error.' );
			exit;
		}
		
		// Set the smartsheet id
		update_post_meta( $post_id, 'smartsheet_id', $smartsheet_id );
		
		// Set the last sync date for this post
		update_post_meta( $post_id, 'smartsheet_last_sync', current_time('Y-m-d H:i:s') );
		
		// Redirect to the edit post screen
		$url = get_edit_post_link( $post_id, 'raw' );
		
		// Add a message to indicate the post was created successfully
		if ( $type == 'village' ) {
			$url = add_query_arg(array('ah_notice' => 'smartsheet_village_inserted'), $url);
		}else{
			$url = add_query_arg(array('ah_notice' => 'smartsheet_hotel_inserted'), $url);
		}
		
		wp_redirect( $url );
		exit;
	}
	
}