<?php

class Class_AH_Smartsheet_Sync_Hotels_And_Villages {
	
	public $columns = array(
		'village_name'    => 'Village Name',
		'village_id'      => 'Village ID',
		
		'hotel_name'      => 'Hotel Name',
		'hotel_id'        => 'Hotel ID',
		
		// Additional hotel details:
		'proprietor_name' => 'Proprietor Name',
		'email'           => 'Email',
		'phone'           => 'Phone',
		
		// Each item also stores the "smartsheet_row_id"
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
		
		// Sync a village or hotel from a link in the settings page
		add_action( 'admin_init', array( $this, 'sync_village_or_hotel_from_link' ) );
		
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
	 *      @type string|null $village_name
	 *      @type string|null $village_id
	 *      @type string|null $hotel_name
	 *      @type string|null $hotel_id
	 *      @type string|null $proprietor_name
	 *      @type string|null $email
	 *      @type string|null $phone
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
	 *      @type string $smartsheet_name
	 *      @type string $smartsheet_id
	 *
	 *      Hotels only:
	 *      @type string $village_id
	 *      @type string $proprietor_name
	 *      @type string $email
	 *      @type string $phone
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
	 *      @type string $smartsheet_name
	 *      @type string $smartsheet_id
	 * }
	 */
	public function get_stored_village_list() {
		$village_list = get_option( 'ah_village_list' );
		if ( empty($village_list) ) $village_list = array();
		
		return $village_list;
	}
	
	/**
	 * Separates hotels which are attached to posts, and those that aren't.
	 *
	 * Returns an array:
	 *     [0] = items that exist on Smartsheet + WordPress
	 *     [1] = items missing from WordPress
	 *     [2] = items missing from Smartsheet
	 *
	 * @param array[] $item_list   array of items which each include "id" and "name"
	 * @param string   $type       either "hotel" or "village"
	 *
	 * @return array {
	 *      [0] [1] [2] = array {
	 *           @type string|null $smartsheet_name
	 *           @type string|null $smartsheet_id
	 *           @type int|null    $post_id
	 *      }
	 * }
	 */
	public function group_by_smartsheet_assignment( $item_list, $type ) {
		$assigned = array();
		$unassigned = array();
		$found_ids = array();
		
		// Check each hotel by name to see if it is assigned to a post
		foreach( $item_list as $item ) {
			$smartsheet_name = $item['smartsheet_name'];
			$smartsheet_id = $item['smartsheet_id'];
			
			if ( $type == 'hotel' ) {
				$post_id = $this->get_hotel_by_smartsheet_id( $smartsheet_id );
			}else{
				$post_id = $this->get_village_by_smartsheet_id( $smartsheet_id );
			}
			
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
		
		// Identify villages/hotels that only exist in WordPress as "missing" from smartsheet
		$missing = array();
		$remaining_ids = $this->get_all_item_ids( $type, $found_ids );
		
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
	
	public function get_all_item_ids( $type, $exclude_ids = array() ) {
		if ( $type == 'hotel' ) {
			$post_type = AH_Hotel()->get_post_type();
		}else{
			$post_type = AH_Village()->get_post_type();
		}
		
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
	 *      @type string $sheet_name
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
			'sheet_name' => $sheet['name'], // "Copy of Master List - Hotel Info"
			'permalink' => $sheet['permalink'], // "https://app.smartsheet.com/sheets/FXq9cXvg56pv22JCpVCPC69mW2j7jf29PHRr7x31"
		);
		
		update_option( 'ah_hotel_sheet', $sheet_data, false );
		
		// Get rows from the sheet
		$rows = AH_Smartsheet_API()->get_rows_from_sheet( $sheet_id );
		if ( ! $rows ) return false;
		
		// 1. Get a list of hotels
		$hotel_list = $this->get_values_from_rows( $rows, array(
			'smartsheet_name' => $column_ids['hotel_name'],
			'smartsheet_id'   => $column_ids['hotel_id'],
			
			// Additional hotel data:
			'village_id'      => $column_ids['village_id'],
			'proprietor_name' => $column_ids['proprietor_name'],
			'email'           => $column_ids['email'],
			'phone'           => $column_ids['phone'],
		) );
		
		// Save the hotel list
		if ( $hotel_list ) {
			update_option( 'ah_hotel_list', $hotel_list, false );
		}
	
		// 2. Get a list of villages
		$village_list = $this->get_values_from_rows( $rows, array(
			'smartsheet_name'  => $column_ids['village_name'],
			'smartsheet_id'    => $column_ids['village_id'],
		));
		
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
				AH_Smartsheet_Sync()->is_cell_valid( $item['smartsheet_name'] )
				&&
				AH_Smartsheet_Sync()->is_cell_valid( $item['smartsheet_id'] )
			) {
				$items[ $item['smartsheet_id'] ] = $item;
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
	 * Get the hotel based on smartsheet hotel name, and assigned village
	 *
	 * @param string $hotel_smartsheet_id
	 * @param int $village_id
	 *
	 * @return false|int
	 */
	public function get_hotel_by_smartsheet_name_and_village( $hotel_smartsheet_id, $village_id ) {
		if ( ! $hotel_smartsheet_id ) return false;
		if ( ! $village_id ) return false;
		
		$args = array(
			'post_type' => 'ah_hotel',
			'meta_query' => array(
				array(
					'key' => 'smartsheet_id',
					'value' => $hotel_smartsheet_id
				),
				array(
					'key' => 'village',
					'value' => (int) $village_id
				),
			),
			'fields' => 'ids',
		);
		
		$q = new WP_Query($args);
		
		return $q->found_posts ? $q->posts[0] : false;
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
			'ah_smartsheet_name' => $smartsheet_name,
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
		if ( $type != 'village' && $type != 'hotel' ) return;
		
		$smartsheet_name = stripslashes($_GET['ah_smartsheet_name']);
		$smartsheet_id = stripslashes($_GET['ah_smartsheet_id']);
		
		if ( $type == 'village' ) {
			$type_name = 'Village';
			$post_type = AH_Village()->get_post_type();
			$existing_post_id = $this->get_village_by_smartsheet_id($smartsheet_id);
		}else{
			$type_name = 'Hotel';
			$post_type = AH_Hotel()->get_post_type();
			$existing_post_id = $this->get_hotel_by_smartsheet_id($smartsheet_id);
		}
		
		// If it already exists, show an error message with link to edit
		if ( $existing_post_id ) {
			$message = sprintf(
				'%s already exists: <a href="%s">%s #%d</a>',
				esc_html($type_name),
				esc_attr(get_edit_post_link($existing_post_id)),
				esc_html($smartsheet_id),
				esc_html($existing_post_id)
			);
			wp_die($message);
			exit;
		}
		
		// Get the Smartsheet data from the list
		$item_data = $this->get_stored_item( $smartsheet_id, $type );
		
		if ( ! $item_data ) {
			wp_die('Cannot create hotel/village: item data not found with smartsheet id "'. esc_html($smartsheet_id) .'"');
			exit;
		}
		
		// Smartsheet ID is used as the post title because some villages/hotels may have repeat names in different locations.
		$post_title = $smartsheet_id;
		
		// Create the post
		$args = array(
			'post_type' => $post_type,
			'post_title' => $post_title,
			'post_status' => 'publish',
		);
		
		// Hotels correspond to a village
		/*
		if ( $type == 'hotel' ) {
			
			$hotel = $this->get_hotel_by_smartsheet_id( $smartsheet_id );
			// @todo: finish creating hotels from button
			echo 'TODO: Finish creating hotels from link';
			pre_dump($hotel);
			exit;
			
		}
		*/
		
		/*
		pre_dump(compact(
			'type', 'smartsheet_name', 'smartsheet_id',
			'type_name', 'post_type', 'existing_post_id'
		));
		pre_dump(compact($args));
		exit;
		*/
		
		$post_id = wp_insert_post( $args );
		
		if ( ! $post_id || is_wp_error( $post_id ) ) {
			wp_die( 'Failed to insert ' . $type_name . ', wp_insert_post returned an error.', 'Error', $post_id );
			exit;
		}
		
		// Sync the item with data from Smartsheet
		$this->sync_item( $type, $post_id, $item_data );
		
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
	
	/**
	 * Get a link that will automatically create a village or hotel based on smartsheet name/id
	 *
	 * @param string $type                  either "village" or "hotel"
	 * @param string $post_id               ID of the post in WordPress
	 * @param string|null $smartsheet_id    Optional. The title which should match the "WordPress ID" column in Smartsheet.
	 *
	 * @return string
	 */
	public function get_sync_village_or_hotel_link( $type, $post_id, $smartsheet_id = null ) {
		$base_url = isset($_GET['page']) ? add_query_arg(array('page' => $_GET['page'] ?? '')) : $_SERVER['REQUEST_URI'];
		
		if ( $smartsheet_id === null ) {
			$smartsheet_id = get_post_meta( $post_id, 'smartsheet_id', true );
			if ( ! $smartsheet_id ) return false;
		}
		
		$args = array(
			'ah_sync_item' => $type,
			'ah_post_id' => $post_id,
			'ah_smartsheet_id' => $smartsheet_id,
		);
		
		$url = add_query_arg($args, $base_url);
		
		return $url;
	}
	
	/**
	 * Returns the item with matching smartsheet ID from the stored village or hotel list.
	 * False if not found.
	 *
	 * @param string $smartsheet_id
	 * @param string $type
	 *
	 * @return array|false
	 */
	public function get_stored_item( $smartsheet_id, $type ) {
		if ( $type == 'village' ) {
			$list = $this->get_stored_village_list();
		}else{
			$list = $this->get_stored_hotel_list();
		}
		
		foreach( $list as $item ) {
			if ( $item['smartsheet_id'] == $smartsheet_id ) {
				return $item;
			}
		}
		return false;
	}
	
	/**
	 * Sync a hotel from a row in the spreadsheet (within the "Sync Villages and Hotels" settings screen)
	 *
	 * @return void
	 */
	public function sync_village_or_hotel_from_link() {
		if ( ! isset($_GET['ah_sync_item']) ) return;
		
		$type = stripslashes($_GET['ah_sync_item']);
		if ( $type != 'village' && $type != 'hotel' ) return;
		
		$post_id = stripslashes($_GET['ah_post_id']);
		$smartsheet_id = stripslashes($_GET['ah_smartsheet_id']);
		
		// Look for the corresponding item to sync with
		$item_data = $this->get_stored_item( $smartsheet_id, $type );
		
		if ( ! $item_data ) {
			wp_die('Cannot sync hotel/village: item data not found with smartsheet id "'. esc_html($smartsheet_id) .'" and type "'. esc_html($type). '"' );
			exit;
		}
		
		// Update the item
		$this->sync_item( $type, $post_id, $item_data );
		
		// Redirect when complete
		$url = get_edit_post_link( $post_id, 'raw' );
		
		// Add a message to indicate the post was created successfully
		if ( $type == 'village' ) {
			$url = add_query_arg(array('ah_notice' => 'smartsheet_village_sync_complete'), $url);
		}else{
			$url = add_query_arg(array('ah_notice' => 'smartsheet_hotel_sync_complete'), $url);
		}
		
		wp_redirect( $url );
		exit;
	}
	
	/**
	 * Sync a hotel or village using data stored from a previous Smartsheet sync.
	 * $data must include an "id" property (smartsheet id) or else returns false without updating the item.
	 *
	 * Does not perform any API queries.
	 *
	 * @param string $type     "hotel" or "village"
	 * @param int    $post_id  post ID of a hotel or village
	 * @param array  $data     {
	 *     @type string $name               "Hotel Schonegg"
	 *     @type string $id                 "Hotel Schonegg | Wengen"
	 *
	 *     (Hotels Only)
	 *     @type string $village_id         "Wengen - CH"
	 *     @type string $proprietor_name    "Jennifer"
	 *     @type string $email              "mail@hotel-schoenegg.ch"
	 *     @type string $phone              "41 33 855 3422"
	 * }
	 *
	 * @return bool
	 */
	public function sync_item( $type, $post_id, $data ) {
		$smartsheet_id = $data['smartsheet_id'];
		$smartsheet_name = $data['smartsheet_name'];
		if ( ! $smartsheet_id ) return false;
		
		// Smartsheet id
		update_post_meta( $post_id, 'smartsheet_id', $smartsheet_id );
		update_post_meta( $post_id, 'smartsheet_name', $smartsheet_name );
		
		// Last sync date
		update_post_meta( $post_id, 'smartsheet_last_sync', current_time('Y-m-d H:i:s') );
		
		// Village fields
		if ( $type == 'village' ) {
			update_post_meta( $post_id, 'village_name', $smartsheet_name );
		}
		
		// Hotel fields
		if ( $type == 'hotel' ) {
			update_post_meta( $post_id, 'hotel_name', $smartsheet_name );
			
			$village_smartsheet_id = $data['village_id'] ?? '';
			update_post_meta( $post_id, 'village_smartsheet_id', $village_smartsheet_id );
			
			$village_post_id = $this->get_village_by_smartsheet_id( $village_smartsheet_id );
			if ( $village_post_id ) {
				update_post_meta( $post_id, 'village', $village_post_id );
			}
			
			update_post_meta( $post_id, 'proprietor_name', $data['proprietor_name'] ?? '' );
			update_post_meta( $post_id, 'email', $data['email'] ?? '' );
			update_post_meta( $post_id, 'phone', $data['phone'] ?? '' );
		}
		
		return true;
	}
	
}

/*
type
string(5) "hotel"
post_id
string(4) "6510"
smartsheet_id
string(23) "Hotel Schonegg | Wengen"
type_name
string(5) "Hotel"
post_type
string(8) "ah_hotel"
found_item
array(6) {
  ["name"]=>
  string(14) "Hotel Schonegg"
  ["id"]=>
  string(23) "Hotel Schonegg | Wengen"
  ["village_id"]=>
  string(11) "Wengen - CH"
  ["proprietor_name"]=>
  string(8) "Jennifer"
  ["email"]=>
  string(23) "mail@hotel-schoenegg.ch"
  ["phone"]=>
  string(14) "41 33 855 3422"
}

 */