<?php

class Class_AH_Smartsheet_Sync_Hotels_And_Villages {
	
	public $columns = array(
		'hotel_name'      => 'Hotel Name',
		'village_name'    => 'Village Name',
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
		$sheet = AH_Smartsheet_API()->get_sheet_by_id( $sheet_id );
		if ( ! $sheet ) return false;
		
		// Store information about the sheet itself
		$sheet_data = array(
			'sheet_id' => $sheet['id'], // 7567715780061060
			'name' => $sheet['name'], // "Copy of Master List - Hotel Info"
			'permalink' => $sheet['permalink'], // "https://app.smartsheet.com/sheets/FXq9cXvg56pv22JCpVCPC69mW2j7jf29PHRr7x31"
		);
		
		update_option( 'ah_hotel_sheet', $sheet_data );
		
		// Get rows from the sheet
		$rows = AH_Smartsheet_API()->get_rows_from_sheet( $sheet_id );
		
		// Get each hotel and village
		$hotel_list = array();
		$village_list = array();
		
		// Loop through each row
		if ( $rows ) foreach( $rows as $row ) {
			// $row keys = id, rowNumber, cells
		
			// Get hotel name from the hotel row
			$hotel_cell = ah_find_in_array( $row['cells'], 'columnId', $column_ids['hotel_name'] );
			$hotel_name = $hotel_cell['value'] ?? false;
			
			if ( AH_Smartsheet_Sync()->is_cell_valid( $hotel_name ) ) {
				$hotel_list[] = $hotel_name;
			}
			
			// Get village name from the hotel row
			$village_cell = ah_find_in_array( $row['cells'], 'columnId', $column_ids['village_name'] );
			$village_name = $village_cell['value'] ?? false;
			
			if ( AH_Smartsheet_Sync()->is_cell_valid( $village_name ) ) {
				$village_list[] = $village_name;
			}
		}
		
		// Remove duplicates and empty values
		if ( $hotel_list ) {
			$hotel_list = array_unique($hotel_list);
			$hotel_list = array_filter($hotel_list);
			
			sort($hotel_list);
			
			update_option( 'ah_hotel_list', $hotel_list );
		}
		
		// Remove duplicates and empty values
		if ( $village_list ) {
			$village_list = array_unique($village_list);
			$village_list = array_filter($village_list);
			
			sort($village_list);
			
			update_option( 'ah_village_list', $village_list );
		}
		
		return array( 'hotel_list' => $hotel_list, 'village_list' => $village_list );
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
	 * @param $smartsheet_name
	 *
	 * @return int|false
	 */
	public function get_hotel_id_by_name( $smartsheet_name ) {
		return AH_Smartsheet_Sync()->get_post_id_from_name( $smartsheet_name, AH_Hotel()->get_post_type(), $this->hotel_list );
	}
	
	/**
	 * Get the post ID of a village by smartsheet name.
	 * If a list of villages was preloaded, finds the post in that list instead of doing an individual query.
	 *
	 * @see Class_AH_Smartsheet_Sync_Hotels_And_Villages::preload_village_post_list()
	 * @see Class_AH_Smartsheet_Sync::get_post_list()
	 *
	 * @param $smartsheet_name
	 *
	 * @return int|false
	 */
	public function get_village_id_by_name( $smartsheet_name ) {
		return AH_Smartsheet_Sync()->get_post_id_from_name( $smartsheet_name, AH_Village()->get_post_type(), $this->village_list );
	}
	
	/**
	 * Create a hotel from a row in the spreadsheet (within the "Sync Villages and Hotels" settings screen)
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
		
		// If it already exists, show an error message with link to edit
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
		
		// Create the post
		$args = array(
			'post_type' => $post_type,
			'post_title' => $title,
			'post_status' => 'publish',
		);
		
		$post_id = wp_insert_post( $args );
		
		if ( ! $post_id || is_wp_error( $post_id ) ) {
			wp_die( 'Failed to insert ' . $type_name . ', wp_insert_post returned an error.' );
			exit;
		}
		
		// Assign the smartsheet name
		update_post_meta( $post_id, 'smartsheet_name', $title );
		
		$url = get_edit_post_link( $post_id, 'raw' );
		
		wp_redirect( $url );
		exit;
	}
	
}