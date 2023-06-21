<?php

class Class_AH_Smartsheet_Sync_Sheets {
	
	public $sheet_list = null; // keys: id, name, accessLevel, permalink, createdAt, modifiedAt
	
	public function __construct() {
		
		// Register ACF settings pages
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 25 );
		
		// Sync sheet button from the settings page
		add_action( 'admin_init', array( $this, 'process_sheets_sync' ) );
		
	}
	
	public function register_admin_menus() {
		if ( function_exists('acf_add_options_page') ) {
			// Smartsheet Settings -> Sync Sheets
			// NOTE: Must be defined by ACF first, then override with a WP submenu page
			acf_add_options_sub_page( array(
				'parent_slug' => 'acf-ah-settings-parent',
				'page_title'  => 'Sync Sheets',
				'menu_title'  => 'Sync Sheets',
				'capability' => 'manage_options',
				'menu_slug'   => 'ah-smartsheet-sheets',
			) );
			add_submenu_page(
				null,
				'Sync Sheets',
				'Sync Sheets',
				'manage_options',
				'ah-smartsheet-sheets',
				array( $this, 'display_admin_page' )
			);
			
		}
	}
	
	public function display_admin_page() {
		include( AH_PATH . '/templates/admin/smartsheet-sheets.php' );
	}
	
	/**
	 * Get list of stored sheets which came from Smartsheet
	 *
	 * @return array[] {
	 *      @type int $id               5187134723254148
	 *      @type string $name          "*2023 (Forrest) Best TMB-0615"
	 *      @type string $accessLevel   "ADMIN"
	 *      @type string $permalink     "https://app.smartsheet.com/sheets/9Jhp3828pWqfC5mfrfPFhJhrpJRJjj78x2xc3pr1"
	 *      @type string $createdAt     "2022-08-15T14:24:42Z"
	 *      @type string $modifiedAt    "2023-05-02T20:17:45Z"
	 * }
	 */
	public function get_stored_sheet_list() {
		$sheet_list = get_option( 'ah_sheet_list' );
		if ( empty($sheet_list) ) $sheet_list = array();
		
		return $sheet_list;
	}
	
	/**
	 * Get a list of Sheets
	 *
	 * @return array|false
	 */
	public function sync_sheets_from_smartsheet() {
		// Get the sheet details
		$results = AH_Smartsheet_API()->list_all_sheets();
		
		$sheet_list = $results ? $results['data'] : false;
		
		// Save sheet list if successful
		if ( $sheet_list ) {
			update_option( 'ah_sheet_list', $sheet_list, false );
		}
		
		// Save the last sync date
		update_option( 'ah_sheets_last_sync', current_time('Y-m-d H:i:s'), false );
		
		return $sheet_list;
	}
	
	/**
	 * When visiting the link to sync from the Sync Sheets page, triggers the sync and does a redirect when successful
	 *
	 * @return void
	 */
	public function process_sheets_sync() {
		if ( ! isset($_GET['ah_sync_sheets']) ) return;
		
		$url = remove_query_arg('ah_sync_sheets');
		
		// Perform the sync with Smartsheet's API
		$results = $this->sync_sheets_from_smartsheet();
		
		if ( $results === false ) {
			// The sync did not complete
			ah_add_alert( 'error', 'Sheet Sync Failed', 'Syncing sheets from smartsheet did not complete successfully. The previously stored sheet information will be preserved.' );
			$url = add_query_arg(array('ah_notice' => 'sync_sheets_failed'), $url);
			wp_redirect($url);
			exit;
		}
		
		// Data to send in the URL, used in the notice popup
		$data = array(
			'sheets' => count($results),
		);
		
		// Build URL to redirect to
		$url = add_query_arg(array(
			'ah_notice' => 'sync_sheets_success',
			'ah_notice_data' => urlencode(json_encode($data))
		), $url);
		
		wp_redirect($url);
		exit;
	}
	
	/**
	 * Get the sheet id, name and url
	 *
	 * @param string|int $sheet_id
	 *
	 * @return array|false
	 */
	public function get_sheet_data( $sheet_id ) {
		if ( ! $sheet_id ) return false;
		
		$sheet = false;
		
		// Check for the sheet in the list of stored sheets, which may skip an unneeded API call
		$stored_sheets = $this->get_stored_sheet_list();
		if ( $stored_sheets ) {
			foreach( $stored_sheets as $i => $s ) {
				if ( $s['id'] == $sheet_id ) {
					$sheet = $s;
					break;
				}
			}
		}
		
		// Get the sheet details using the Smartsheet API
		if ( ! $sheet ) {
			$sheet = AH_Smartsheet_API()->get_sheet_by_id( $sheet_id );
		}
		
		// If still not found
		if ( ! $sheet ) {
			return false;
		}
		
		// Return information about the sheet itself
		return array(
			'sheet_id' => $sheet['id'], // 7567715780061060
			'sheet_name' => $sheet['name'], // "Copy of Master List - Hotel Info"
			'permalink' => $sheet['permalink'], // "https://app.smartsheet.com/sheets/FXq9cXvg56pv22JCpVCPC69mW2j7jf29PHRr7x31"
		);
	}
	
}