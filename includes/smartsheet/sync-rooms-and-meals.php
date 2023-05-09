<?php

class Class_AH_Smartsheet_Sync_Rooms_And_Meals {
	
	public $columns = array(
		'room_code'       => 'Room Code',
		'room_name'       => 'Room Name',
		
		'meal_code'       => 'Meal Code',
		'meal_name_short' => 'Meal Name (Short)',
		'meal_name_full'  => 'Meal Name (Full)',
		// Each room and meal item also includes "smartsheet_row_id" which is not displayed
		// Rooms and Meals can share a row ID
	);
	
	public $room_list = null; // keys: room_code, room_name
	public $meal_list = null; // keys: meal_code, meal_name_short, meal_name_full
	
	public function __construct() {
		
		// Register ACF settings pages
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 25 );
		
		// Save settings page
		add_action( 'admin_init', array( $this, 'save_admin_menu_settings' ) );
		
		// Sync rooms button from the settings page
		add_action( 'admin_init', array( $this, 'process_rooms_and_meals_sync' ) );
		
	}
	
	public function register_admin_menus() {
		if ( function_exists('acf_add_options_page') ) {
			// Smartsheet Settings -> Sync Rooms and Meals
			// NOTE: Must be defined by ACF first, then override with a WP submenu page
			acf_add_options_sub_page( array(
				'parent_slug' => 'acf-ah-settings-parent',
				'page_title'  => 'Sync Rooms and Meals',
				'menu_title'  => 'Sync Rooms and Meals',
				'capability' => 'manage_options',
				'menu_slug'   => 'ah-smartsheet-rooms-and-meals',
			) );
			add_submenu_page(
				null,
				'Sync Rooms and Meals',
				'Sync Rooms and Meals',
				'manage_options',
				'ah-smartsheet-rooms-and-meals',
				array( $this, 'display_admin_page' )
			);
			
		}
	}
	
	public function display_admin_page() {
		include( AH_PATH . '/templates/admin/smartsheet-rooms-and-meals.php' );
	}
	
	public function save_admin_menu_settings() {
		$action = $_POST['ah-action'] ?? false;
		if ( ! wp_verify_nonce( $action, 'save-room-info' ) ) return;
		
		$data = stripslashes_deep($_POST['ah']);
		
		// Sheet ID
		$sheet_id = $data['sheet_id'];
		update_option( 'ah_rooms_and_meals_sheet_id', $sheet_id, false );
		
		// Column IDs
		$column_ids = $this->format_column_ids( $data['column_ids'] );
		update_option( 'ah_rooms_and_meals_column_ids', $column_ids, false );
		
		// Reload the form (to clear post data from browser history)
		wp_redirect(add_query_arg(array('ah_notice' => 'room_list_updated')));
		exit;
	}
	
	public function get_sheet_id() {
		return get_option( 'ah_rooms_and_meals_sheet_id' );
	}
	
	public function get_column_ids() {
		$column_ids = get_option( 'ah_rooms_and_meals_column_ids' );
		
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
	 *      @type string|null $room_code
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
	 *      @type string $room_code
	 *      @type string $meal_name
	 * }
	 */
	public function get_stored_room_list() {
		$room_list = get_option( 'ah_room_list' );
		if ( empty($room_list) ) $room_list = array();
		
		return $room_list;
	}
	
	/**
	 * Get an array of meals used by the room list. Returns just the meal names.
	 *
	 * @return string[]
	 */
	public function get_stored_meal_list() {
		$meal_list = get_option( 'ah_meal_list' );
		if ( empty($meal_list) ) $meal_list = array();
		
		return $meal_list;
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
		$sheet_data = get_option( 'ah_meals_and_rooms_sheet' );
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
	 * Get a list of room and meal names from the master room spreadsheet
	 *
	 * @return array|false
	 */
	public function sync_rooms_and_meals_from_smartsheet() {
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
			'sheet_id' => $sheet['id'], // 
			'name' => $sheet['name'], // "Copy of Master List - Rooms Meals Month Day"
			'permalink' => $sheet['permalink'], // "https://app.smartsheet.com/sheets/325jvc9vPfWQmRJPvFgvJWfVMjRw3H3GrPjW8651"
		);
		
		update_option( 'ah_meals_and_rooms_sheet', $sheet_data, false );
		
		// Get rows from the sheet
		$rows = AH_Smartsheet_API()->get_rows_from_sheet( $sheet_id );
		
		// Get each room and meal
		$room_list = array();
		$meal_list = array();
		
		// [1/2] Loop through each row to get room codes and names
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
		
		// [1/2] Loop through each row to get meal codes, short names, and full names
		if ( $rows ) foreach( $rows as $row ) {
			// $row keys = id, rowNumber, cells
		
			// Get meal code and name from the row
			$meal_code_cell = ah_find_in_array( $row['cells'], 'columnId', $column_ids['meal_code'] );
			$meal_code = $meal_code_cell['value'] ?? false;
			
			$meal_name_short_cell = ah_find_in_array( $row['cells'], 'columnId', $column_ids['meal_name_short'] );
			$meal_name_short = $meal_name_short_cell['value'] ?? false;
			
			$meal_name_full_cell = ah_find_in_array( $row['cells'], 'columnId', $column_ids['meal_name_full'] );
			$meal_name_full = $meal_name_full_cell['value'] ?? false;
			
			if (
				AH_Smartsheet_Sync()->is_cell_valid( $meal_code )
				&& AH_Smartsheet_Sync()->is_cell_valid( $meal_name_short )
				&& AH_Smartsheet_Sync()->is_cell_valid( $meal_name_full )
			) {
				$meal_list[ $meal_code ] = array(
					'meal_code' => $meal_code,
					'meal_name_short' => $meal_name_short,
					'meal_name_full' => $meal_name_full,
				);
			}
		}
		
		// Remove duplicates and empty values
		if ( $room_list ) {
			update_option( 'ah_room_list', $room_list, false );
		}
		
		// Remove duplicates and empty values
		if ( $meal_list ) {
			update_option( 'ah_meal_list', $meal_list, false );
		}
		
		// Save the last sync date
		update_option( 'ah_rooms_and_meals_last_sync', current_time('Y-m-d H:i:s'), false );
		
		return array( 'room_list' => $room_list, 'meal_list' => $meal_list );
	}
	
	/**
	 * When visiting the link to sync from the Sync Rooms and Meals page, triggers the sync and does a redirect when successful
	 *
	 * @return void
	 */
	public function process_rooms_and_meals_sync() {
		if ( ! isset($_GET['ah_sync_rooms_and_meals']) ) return;
		
		$url = remove_query_arg('ah_sync_rooms_and_meals');
		
		// Perform the sync with Smartsheet's API
		$result = $this->sync_rooms_and_meals_from_smartsheet();
		
		if ( $result === false ) {
			// The sync did not complete
			ah_add_alert( 'error', 'Room and Meal Sync Failed', 'Syncing room information from smartsheet did not complete successfully. The previously stored room and meal information will be preserved.' );
			$url = add_query_arg(array('ah_notice' => 'sync_rooms_failed'), $url);
			wp_redirect($url);
			exit;
		}
		
		// Data to send in the URL, used in the notice popup
		$data = array(
			'rooms' => count($result['room_list']),
			'meals' => count($result['meal_list']),
		);
		
		// Build URL to redirect to
		$url = add_query_arg(array(
			'ah_notice' => 'sync_rooms_success',
			'ah_notice_data' => urlencode(json_encode($data))
		), $url);
		
		wp_redirect($url);
		exit;
	}
	
}