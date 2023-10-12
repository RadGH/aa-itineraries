<?php

class Class_AH_Smartsheet_Sync_Rooms {
	
	public $columns = array(
		'room_code'       => 'Room Code',
		'room_name'       => 'Room Name',
		
		// Each room item also includes "smartsheet_row_id" which is not displayed
	);
	
	public $room_list = null; // keys: room_code, room_name
	
	public function __construct() {
		
		// Register ACF settings pages
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 25 );
		
		// Save settings page
		add_action( 'admin_init', array( $this, 'save_admin_menu_settings' ) );
		
		// Sync rooms button from the settings page
		add_action( 'admin_init', array( $this, 'process_rooms_sync' ) );
		
	}
	
	public function register_admin_menus() {
		if ( function_exists('acf_add_options_page') ) {
			// Smartsheet Settings -> Sync Rooms
			// NOTE: Must be defined by ACF first, then override with a WP submenu page
			acf_add_options_sub_page( array(
				'parent_slug' => 'acf-ah-settings-parent',
				'page_title'  => 'Sync Rooms',
				'menu_title'  => 'Sync Rooms',
				'capability' => 'manage_options',
				'menu_slug'   => 'ah-smartsheet-rooms',
			) );
			add_submenu_page(
				null,
				'Sync Rooms',
				'Sync Rooms',
				'manage_options',
				'ah-smartsheet-rooms',
				array( $this, 'display_admin_page' )
			);
			
		}
	}
	
	public function display_admin_page() {
		include( AH_PATH . '/templates/admin/smartsheet-rooms.php' );
	}
	
	public function save_admin_menu_settings() {
		$action = $_POST['ah-action'] ?? false;
		if ( ! wp_verify_nonce( $action, 'save-room-info' ) ) return;
		
		$data = stripslashes_deep($_POST['ah']);
		
		// Sheet ID
		$sheet_id = $data['sheet_id'];
		update_option( 'ah_rooms_sheet_id', $sheet_id, false );
		
		// Column IDs
		$column_ids = ah_prepare_columns( $this->columns,  $data['column_ids'] );
		update_option( 'ah_rooms_column_ids', $column_ids, false );
		
		// Reload the form (to clear post data from browser history)
		wp_redirect(add_query_arg(array('ah_notice' => 'room_list_updated')));
		exit;
	}
	
	public function get_sheet_id() {
		return get_option( 'ah_rooms_sheet_id' );
	}
	
	public function get_column_ids() {
		$column_ids = get_option( 'ah_rooms_column_ids' );
		
		$column_ids = ah_prepare_columns( $this->columns,  $column_ids );
		
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
	 *      @type string|null $room_code
	 * }
	 */
	public function format_column_ids( $column_data ) {
		$template = array_fill_keys( array_keys($this->columns), null );
		return ah_prepare_atts( $template, $column_data );
	}
	
	/**
	 * Get list of stored room data which came from Smartsheet
	 *
	 * @return array {
	 *      @type int $smartsheet_row_id
	 *      @type string $room_code
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
		$sheet_data = get_option( 'ah_rooms_sheet' );
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
	 * Get a list of room names from the master room spreadsheet
	 *
	 * @return array|false
	 */
	public function sync_rooms_from_smartsheet() {
		// Get information about the sheet
		$sheet = AH_Smartsheet_Sync_Sheets()->get_sheet_data( $this->get_sheet_id() );
		if ( ! $sheet ) return false;
		
		// Get column IDs to use for structure
		$column_ids = $this->get_column_ids();
		if ( ! $column_ids ) return false;
		
		
		update_option( 'ah_rooms_sheet', $sheet, false );
		
		// Get rows from the sheet
		$rows = AH_Smartsheet_API()->get_rows_from_sheet( $sheet['sheet_id'] );
		
		// Get each room's code and name
		$room_list = array();
		
		// Loop through each row to get room codes and names
		if ( $rows ) foreach( $rows as $row ) {
			// $row keys = id, rowNumber, cells
		
			// Get room code and name from the row
			$room_code_cell = ah_find_in_array( $row['cells'], 'columnId', $column_ids['room_code'] );
			$room_code = $room_code_cell['value'] ?? false;
			
			$room_name_cell = ah_find_in_array( $row['cells'], 'columnId', $column_ids['room_name'] );
			$room_name = $room_name_cell['value'] ?? false;
			
			if (
				AH_Smartsheet_Sync()->is_cell_valid( $room_code )
				&& AH_Smartsheet_Sync()->is_cell_valid( $room_name )
			) {
				$room_list[ $room_code ] = array(
					'room_code' => $room_code,
					'room_name' => $room_name,
				);
			}
		}
		
		// Save the values if successful
		if ( $room_list ) {
			update_option( 'ah_room_list', $room_list, false );
		}
		
		// Save the last sync date
		update_option( 'ah_rooms_last_sync', current_time('Y-m-d H:i:s'), false );
		
		return $room_list;
	}
	
	/**
	 * When visiting the link to sync from the Sync Rooms page, triggers the sync and does a redirect when successful
	 *
	 * @return void
	 */
	public function process_rooms_sync() {
		if ( ! isset($_GET['ah_sync_rooms']) ) return;
		
		$url = remove_query_arg('ah_sync_rooms');
		
		// Perform the sync with Smartsheet's API
		$result = $this->sync_rooms_from_smartsheet();
		
		if ( $result === false ) {
			// The sync did not complete
			ah_add_alert( 'error', 'Room Sync Failed', 'Syncing room information from smartsheet did not complete successfully. The previously stored room information will be preserved.' );
			$url = add_query_arg(array('ah_notice' => 'sync_rooms_failed'), $url);
			wp_redirect($url);
			exit;
		}
		
		// Data to send in the URL, used in the notice popup
		$data = array(
			'rooms' => count($result),
		);
		
		// Build URL to redirect to
		$url = add_query_arg(array(
			'ah_notice' => 'sync_rooms_success',
			'ah_notice_data' => urlencode(json_encode($data))
		), $url);
		
		wp_redirect($url);
		exit;
	}
	
	/**
	 * Get a room by room code. Optionally a specific field for that room (room_code, room_name)
	 *
	 * @param $room_code
	 * @param $field_name
	 *
	 * @return string|array
	 */
	public function get_room( $room_code, $field_name = null ) {
		$list = $this->get_stored_room_list();
		// room_code
		// room_name
		
		$room = $list[$room_code] ?? null;
		
		if ( $field_name && $room ) {
			return $room[$field_name] ?? null;
		}else{
			return $room;
		}
	}
	
}