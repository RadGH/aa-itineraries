<?php

class Class_AH_Smartsheet_Hotels  {
	
	public $columns = array(
		'hotel_name'      => 'Hotel Name',
		'village_name'    => 'Village Name',
		// Each hotel item also includes "smartsheet_row_id" which is not displayed
	);
	
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
			// Smartsheet Settings -> Hotel Info
			// NOTE: Must be defined by ACF first, then override with a WP submenu page
			acf_add_options_sub_page( array(
				'parent_slug' => 'acf-ah-settings-parent',
				'page_title'  => 'Villages and Hotels',
				'menu_title'  => 'Villages and Hotels',
				'capability' => 'manage_options',
				'menu_slug'   => 'ah-smartsheet-hotel-info',
			) );
			add_submenu_page(
				null,
				'Villages and Hotels',
				'Villages and Hotels',
				'manage_options',
				'ah-smartsheet-hotel-info',
				array( $this, 'display_admin_page_hotel_info' )
			);
			
		}
	}
	
	public function display_admin_page_hotel_info() {
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
	 *      @type int $smartsheet_row_id
	 *      @type string $hotel_name
	 *      @type string $village_name
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
	 * @return string[]
	 */
	public function get_stored_village_list() {
		$village_list = get_option( 'ah_village_list' );
		if ( empty($village_list) ) $village_list = array();
		
		return $village_list;
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
		$sheet = AH_Smartsheet()->get_sheet_by_id( $sheet_id );
		if ( ! $sheet ) return false;
		
		// Store information about the sheet itself
		$sheet_data = array(
			'sheet_id' => $sheet['id'], // 7567715780061060
			'name' => $sheet['name'], // "Copy of Master List - Hotel Info"
			'permalink' => $sheet['permalink'], // "https://app.smartsheet.com/sheets/FXq9cXvg56pv22JCpVCPC69mW2j7jf29PHRr7x31"
		);
		
		update_option( 'ah_hotel_sheet', $sheet_data );
		
		// Get rows from the sheet
		$rows = AH_Smartsheet()->get_rows_from_sheet( $sheet_id );
		
		// Get each hotel and village
		$hotel_list = array();
		$village_list = array();
		
		// Loop through each row
		if ( $rows ) foreach( $rows as $row ) {
			// $row keys = id, rowNumber, cells
		
			// Get hotel name from the hotel row
			$hotel_cell = ah_find_in_array( $row['cells'], 'columnId', $column_ids['hotel_name'] );
			$hotel_name = $hotel_cell['value'] ?? false;
			
			if ( $hotel_name && $hotel_name != '#INVALID OPERATION' ) {
				$hotel_list[] = $hotel_name;
			}
			
			// Get village name from the hotel row
			$village_cell = ah_find_in_array( $row['cells'], 'columnId', $column_ids['village_name'] );
			$village_name = $village_cell['value'] ?? false;
			
			if ( $village_name && $village_name != '#INVALID OPERATION' ) {
				$village_list[] = $village_name;
			}
		}
		
		// Remove duplicates and empty values
		if ( $hotel_list ) {
			$hotel_list = array_unique($hotel_list);
			$hotel_list = array_filter($hotel_list);
			
			update_option( 'ah_hotel_list', $hotel_list );
		}
		
		// Remove duplicates and empty values
		if ( $village_list ) {
			$village_list = array_unique($village_list);
			$village_list = array_filter($village_list);
			
			update_option( 'ah_village_list', $village_list );
		}
		
		return array( 'hotel_list' => $hotel_list, 'village_list' => $village_list );
	}
	
	/**
	 * When visiting the link to sync from the hotel info page, loads the hotel sheet from Smartsheet and updates each row.
	 *
	 * @return void
	 */
	public function process_hotel_info_sync() {
		if ( ! isset($_GET['ah_sync_hotels']) ) return;
		
		$url = remove_query_arg('ah_sync_hotels');
		
		// Perform the sync with Smartsheet's API
		$result = $this->sync_hotel_info_from_smartsheet();
		
		if ( $result === false ) {
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
	 * Gets stored hotel information for a single hotel, based on the row ID from smartsheet (key: smartsheet_row_id)
	 *
	 * @param $row_id
	 *
	 * @return array|false
	 */
	/*
	public function get_hotel_by_row_id( $row_id ) {
		$hotel_list = $this->get_stored_hotel_list();
		
		$hotel = ah_find_in_array( $hotel_list, 'smartsheet_row_id', $row_id );
		
		return $hotel ?: false;
	}
	*/
	
	/**
	 * Get the post ID of a hotel by the hotel name. The hotel name must exactly match the post title.
	 * 
	 * @param $hotel_name
	 *
	 * @return int|false
	 */
	public function get_hotel_id_by_name( $hotel_name ) {
		global $wpdb;
		
		$sql = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_status IN ( 'publish', 'draft', 'private', 'pending' ) LIMIT 1;",
			$hotel_name,
			AH_Hotel()->get_post_type()
		);
		
		$id = (int) $wpdb->get_var( $sql );
		
		return $id ?: false;
	}
	
	/**
	 * Get the post ID of a village by the village name. The village name must exactly match the post title.
	 * 
	 * @param $village_name
	 *
	 * @return int|false
	 */
	public function get_village_id_by_name( $village_name ) {
		global $wpdb;
		
		$sql = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_status IN ( 'publish', 'draft', 'private', 'pending' ) LIMIT 1;",
			$village_name,
			AH_Village()->get_post_type()
		);
		
		$id = (int) $wpdb->get_var( $sql );
		
		return $id ?: false;
	}
	
	/**
	 * Create a hotel from a row in the spreadsheet (within the "Hotel Info" settings screen)
	 *
	 * @return void
	 */
	public function create_village_or_hotel_from_link() {
		
		if ( isset($_GET['ah_create_village']) ) {
			$type_name = 'Village';
			$title = stripslashes($_GET['ah_create_village']);
			$post_type = AH_Village()->get_post_type();
			$existing_post_id = $this->get_village_id_by_name($title);
			
		}else if ( isset($_GET['ah_create_hotel']) ) {
			$type_name = 'Hotel';
			$title = stripslashes($_GET['ah_create_hotel']);
			$post_type = AH_Hotel()->get_post_type();
			$existing_post_id = $this->get_hotel_id_by_name($title);
			
		}else{
			return;
		}
		
		if ( $existing_post_id ) {
			$message = sprintf(
				'%s already exists: <a href="%s">%s #%d</a>',
				esc_html($type_name),
				esc_attr(get_edit_post_link($existing_post_id)),
				esc_html($title),
				esc_html($existing_post_id)
			);
			wp_die($message);
			exit;
		}
		
		echo 'TODO: Create ' . $type_name . ' with title ' . $title;
		exit;
	}
	
}