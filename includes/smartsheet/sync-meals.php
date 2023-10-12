<?php

class Class_AH_Smartsheet_Sync_Meals {
	
	public $columns = array(
		'meal_code'       => 'Meal Code',
		'meal_name_short' => 'Meal Name (Short)',
		'meal_name_full'  => 'Meal Name (Full)',
		// Each meal item also includes "smartsheet_row_id" which is not displayed
	);
	
	public $meal_list = null; // keys: meal_code, meal_name_short, meal_name_full
	
	public function __construct() {
		
		// Register ACF settings pages
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 25 );
		
		// Save settings page
		add_action( 'admin_init', array( $this, 'save_admin_menu_settings' ) );
		
		// Sync meals button from the settings page
		add_action( 'admin_init', array( $this, 'process_meals_sync' ) );
		
	}
	
	public function register_admin_menus() {
		if ( function_exists('acf_add_options_page') ) {
			// Smartsheet Settings -> Sync Meals
			// NOTE: Must be defined by ACF first, then override with a WP submenu page
			acf_add_options_sub_page( array(
				'parent_slug' => 'acf-ah-settings-parent',
				'page_title'  => 'Sync Meals',
				'menu_title'  => 'Sync Meals',
				'capability' => 'manage_options',
				'menu_slug'   => 'ah-smartsheet-meals',
			) );
			add_submenu_page(
				null,
				'Sync Meals',
				'Sync Meals',
				'manage_options',
				'ah-smartsheet-meals',
				array( $this, 'display_admin_page' )
			);
			
		}
	}
	
	public function display_admin_page() {
		include( AH_PATH . '/templates/admin/smartsheet-meals.php' );
	}
	
	public function save_admin_menu_settings() {
		$action = $_POST['ah-action'] ?? false;
		if ( ! wp_verify_nonce( $action, 'save-meal-data' ) ) return;
		
		$data = stripslashes_deep($_POST['ah']);
		
		// Sheet ID
		$sheet_id = $data['sheet_id'];
		update_option( 'ah_meals_sheet_id', $sheet_id, false );
		
		// Column IDs
		$column_ids = ah_prepare_columns( $this->columns,  $data['column_ids'] );
		update_option( 'ah_meals_column_ids', $column_ids, false );
		
		// Reload the form (to clear post data from browser history)
		wp_redirect(add_query_arg(array('ah_notice' => 'meals_list_updated')));
		exit;
	}
	
	public function get_sheet_id() {
		return get_option( 'ah_meals_sheet_id' );
	}
	
	public function get_column_ids() {
		$column_ids = get_option( 'ah_meals_column_ids' );
		
		$column_ids = ah_prepare_columns( $this->columns,  $column_ids );
		
		return $column_ids;
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
		$sheet_data = get_option( 'ah_meals_sheet' );
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
	 * Get a list of meal names from the master meals spreadsheet
	 *
	 * @return array|false
	 */
	public function sync_meals_from_smartsheet() {
		// Get information about the sheet
		$sheet = AH_Smartsheet_Sync_Sheets()->get_sheet_data( $this->get_sheet_id() );
		if ( ! $sheet ) return false;
		
		// Get column IDs to use for structure
		$column_ids = $this->get_column_ids();
		if ( ! $column_ids ) return false;
		
		update_option( 'ah_meals_sheet', $sheet, false );
		
		// Get rows from the sheet
		$rows = AH_Smartsheet_API()->get_rows_from_sheet( $sheet['sheet_id'] );
		
		// Get each meal
		$meal_list = array();
		
		// Loop through each row to get meal codes, short names, and full names
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
		
		if ( $meal_list ) {
			update_option( 'ah_meal_list', $meal_list, false );
		}
		
		// Save the last sync date
		update_option( 'ah_meals_last_sync', current_time('Y-m-d H:i:s'), false );
		
		return $meal_list;
	}
	
	/**
	 * When visiting the link to sync from the Sync Meals page, triggers the sync and does a redirect when successful
	 *
	 * @return void
	 */
	public function process_meals_sync() {
		if ( ! isset($_GET['ah_sync_meals']) ) return;
		
		$url = remove_query_arg('ah_sync_meals');
		
		// Perform the sync with Smartsheet's API
		$result = $this->sync_meals_from_smartsheet();
		
		if ( $result === false ) {
			// The sync did not complete
			ah_add_alert( 'error', 'Meal Sync Failed', 'Syncing meal information from smartsheet did not complete successfully. The previously stored meal information will be preserved.' );
			$url = add_query_arg(array('ah_notice' => 'sync_meals_failed'), $url);
			wp_redirect($url);
			exit;
		}
		
		// Data to send in the URL, used in the notice popup
		$data = array(
			'meals' => count($result),
		);
		
		// Build URL to redirect to
		$url = add_query_arg(array(
			'ah_notice' => 'sync_meals_success',
			'ah_notice_data' => urlencode(json_encode($data))
		), $url);
		
		wp_redirect($url);
		exit;
	}
	
	/**
	 * Get a meal by meal code. Optionally a specific field for that meal (meal_code, meal_name_short, meal_name_full)
	 *
	 * @param $meal_code
	 * @param $field_name
	 *
	 * @return string|array
	 */
	public function get_meal( $meal_code, $field_name = null ) {
		$list = $this->get_stored_meal_list();
		// meal_code
		// meal_name_short
		// meal_name_full
		
		$meal = $list[$meal_code] ?? null;
		
		if ( $field_name && $meal ) {
			return $meal[$field_name] ?? null;
		}else{
			return $meal;
		}
	}
	
}