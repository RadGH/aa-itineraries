<?php

class Class_AH_Smartsheet_Rooms  {
	
	public $columns = array(
		'room_name'      => 'Room Name',
		'proprietor_name' => 'Proprietor Name',
		'location'        => 'Location',
		'email'           => 'Email',
		'phone'           => 'Phone',
		'wordpress_id'    => 'WordPress ID',
		
		// Each room also includes "smartsheet_row_id" which is not displayed
	);
	
	public function __construct() {
		
		// Register ACF settings pages
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 25 );
		
		// Save settings page
		add_action( 'admin_init', array( $this, 'save_admin_menu_settings' ) );
		
		// Sync rooms button from the settings page
		add_action( 'admin_init', array( $this, 'process_room_info_sync' ) );
		
	}
	
	public function register_admin_menus() {
		if ( function_exists('acf_add_options_page') ) {
			// Smartsheet Settings -> Room Info
			// NOTE: Must be defined by ACF first, then override with a WP submenu page
			acf_add_options_sub_page( array(
				'parent_slug' => 'acf-ah-settings-parent',
				'page_title'  => 'Room Info',
				'menu_title'  => 'Room Info',
				'capability' => 'manage_options',
				'menu_slug'   => 'ah-smartsheet-room-info',
			) );
			add_submenu_page(
				null,
				'Room Info',
				'Room Info',
				'manage_options',
				'ah-smartsheet-room-info',
				array( $this, 'display_admin_page_room_info' )
			);
			
		}
	}
	
	public function display_admin_page_room_info() {
		include( AH_PATH . '/templates/admin/smartsheet-room-info.php' );
	}
	
	public function save_admin_menu_settings() {
		$action = $_POST['ah-action'] ?? false;
		if ( ! wp_verify_nonce( $action, 'save-room-info' ) ) return;
		
		$data = stripslashes_deep($_POST['ah']);
		
		// Sheet ID
		$sheet_id = $data['sheet_id'];
		update_option( 'ah_room_info_sheet_id', $sheet_id, false );
		
		// Column IDs
		$column_ids = $this->format_column_ids( $data['column_ids'] );
		update_option( 'ah_room_info_column_ids', $column_ids, false );
		
		// Reload the form (to clear post data from browser history)
		wp_redirect(add_query_arg(array('ah_notice' => 'room_list_updated')));
		exit;
	}
	
	public function get_sheet_id() {
		return get_option( 'ah_room_info_sheet_id' );
	}
	
	public function get_column_ids() {
		$column_ids = get_option( 'ah_room_info_column_ids' );
		
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
	 *      @type string|null $room_name
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
	 * Get list of stored room data which came from Smartsheet
	 *
	 * @return array {
	 *      @type int $smartsheet_row_id
	 *      @type string $room_name
	 *      @type string $proprietor_name
	 *      @type string $location
	 *      @type string $email
	 *      @type string $phone
	 *      @type int $wordpress_id
	 * }
	 */
	public function get_stored_room_list() {
		$room_list = get_option( 'ah_room_list' );
		if ( empty($room_list) ) $room_list = array();
		
		return $room_list;
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
		$sheet_data = get_option( 'ah_room_sheet' );
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
	 * Get a formatted list of room data from the master room sheet which is defined in Smartsheet Settings > Room Info
	 *
	 * @return bool
	 */
	public function sync_room_info_from_smartsheet() {
		// Get the sheet ID
		$sheet_id = $this->get_sheet_id();
		if ( ! $sheet_id ) return false;
		
		// Get column IDs to use for structure
		$column_ids = $this->get_column_ids();
		if ( ! $column_ids ) return false;
		
		// Get the sheet details
		$sheet = AH_Smartsheet()->get_sheet_by_id( $sheet_id );
		if ( ! $sheet ) return false;
		
		// Store information about the sheet itself
		$sheet_data = array(
			'sheet_id' => $sheet['id'], // 7567715780061060
			'name' => $sheet['name'], // "Copy of Master List - Room Info"
			'permalink' => $sheet['permalink'], // "https://app.smartsheet.com/sheets/FXq9cXvg56pv22JCpVCPC69mW2j7jf29PHRr7x31"
		);
		
		update_option( 'ah_room_sheet', $sheet_data );
		
		// Get rows from the sheet
		$rows = AH_Smartsheet()->get_rows_from_sheet( $sheet_id );
		
		// Get each room
		$rooms = array();
		
		// Loop through each row
		if ( $rows ) foreach( $rows as $r ) {
			// $r = id, rowNumber, cells
			$id = $r['id'];
			$raw_cells = $r['cells'];
			
			// Set up the room item
			$room = array();
			$room['smartsheet_row_id'] = $id;
			
			// Locate each cell by the cell ID from the settings page.
			foreach( $column_ids as $key => $column_id ) {
				$cell = ah_find_in_array( $raw_cells, 'columnId', $column_id );
				$room[$key] = $cell['value'] ?? null;
			}
			
			// Stop at the first empty room
			if ( empty($room['room_name']) ) break;
			
			$rooms[$id] = $room;
		}
		
		if ( empty($rooms) ) return false;
		
		
		update_option( 'ah_room_list', $rooms );
		
		return true;
	}
	
	/**
	 * When visiting the link to sync from the room info page, loads the room sheet from Smartsheet and updates each row.
	 *
	 * @return void
	 */
	public function process_room_info_sync() {
		if ( ! isset($_GET['ah_sync_rooms']) ) return;
		
		$url = remove_query_arg('ah_sync_rooms');
		
		$room_list = $this->sync_room_info_from_smartsheet();
		
		if ( $room_list === null ) {
			$url = add_query_arg(array('ah_notice' => 'sync_rooms_failed'), $url);
			wp_redirect($url);
			exit;
		}
		
		$url = add_query_arg(array('ah_notice' => 'sync_rooms_success'), $url);
		wp_redirect($url);
		exit;
	}
	
}