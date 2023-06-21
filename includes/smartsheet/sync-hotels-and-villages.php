<?php

class Class_AH_Smartsheet_Sync_Hotels_And_Villages {
	
	public $columns = array(
		'hotel_id'        => 'Hotel',               // "Viallet - Areches"
		'hotel_name'      => 'Hotel Name',          // "Areches, Hotel Viallet"
		'village_id'      => 'Location',            // "Areches - FR"
		'region'          => 'Region',              // "TMB"
		'proprietor_name' => 'Proprietor Name',     // "Brigitte"
		'email'           => 'Email',               // "contact@hotelviallet.com"
		'phone'           => 'Phone',               // "33 479 38 1047"
		// "village_name" (from village_id)         // "Areches"
		// "village_code" (from village_id)         // "FR"
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
		$column_ids = ah_prepare_columns( $this->columns,  $data['column_ids'] );
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
	 *      @type string|null $hotel_id        "Viallet - Areches"
	 *      @type string|null $hotel_name      "Areches, Hotel Viallet"
	 *      @type string|null $village_id      "Areches - FR"
	 *      @type string|null $region          "TMB"
	 *      @type string|null $proprietor_name "Brigitte"
	 *      @type string|null $email           "contact@hotelviallet.com"
	 *      @type string|null $phone           "33 479 38 1047"
	 * }
	 */
	public function format_column_ids( $column_data ) {
		$template = array_fill_keys( array_keys($this->columns), null );
		return ah_prepare_atts( $template, $column_data );
	}
	
	/**
	 * Get list of stored hotel data which came from Smartsheet
	 *
	 * @return array {
	 *      @type string $smartsheet_name  "Areches, Hotel Viallet"
	 *      @type string $smartsheet_id    "Viallet - Areches"
	 *      @type string $region           "TMB"
	 *      @type string $village_id       "Areches - FR"
	 *      @type string $village_name     "Areches"
	 *      @type string $village_code     "FR"
	 *      @type string $proprietor_name  "Brigitte"
	 *      @type string $email            "contact@hotelviallet.com"
	 *      @type string $phone            "33 479 38 1047"
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
	 *      @type string $smartsheet_name   "Areches"
	 *      @type string $smartsheet_id     "Areches - FR"
	 *      @type string $region            "TMB"
	 *      @type string $village_code      "FR"
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
		// Get information about the sheet
		$sheet = AH_Smartsheet_Sync_Sheets()->get_sheet_data( $this->get_sheet_id() );
		if ( ! $sheet ) return false;
		
		// Save sheet information
		update_option( 'ah_hotel_sheet', $sheet, false );
		
		// Get column IDs to use for structure
		$column_ids = $this->get_column_ids();
		if ( ! $column_ids ) return false;
		
		// Get rows from the sheet
		$rows = AH_Smartsheet_API()->get_rows_from_sheet( $sheet['sheet_id'] );
		if ( ! $rows ) return false;
		
		// Format the rows to match our intended columns array
		// Before: $row[0]['cells'][1]['value'] = "Steinbock - Gasterntal"
		// After:  $row[0]['hotel']             = "Steinbock - Gasterntal"
		$rows = AH_Smartsheet_Sync()->get_values_from_rows( $rows, 'hotel_id', $column_ids );
		
		// Perform some extra changes on each row
		foreach( $rows as &$row ) {
			// Extract village name and code from the ID
			list( $village_name, $village_code ) = $this->split_village_id( $row['village_id'] );
			$row['village_name'] = $village_name;
			$row['village_code'] = $village_code;
			
			// Remove the redundant village name from the hotel name
			$s = $village_name . ', ';
			if ( $s ) {
				$row['hotel_name'] = str_replace( $s, '', $row['hotel_name']);
			}
		}
		
		// Create a list of hotels
		$hotel_list = $this->get_hotels_from_rows( $rows );
		
		// Save the hotel list
		if ( $hotel_list ) {
			update_option( 'ah_hotel_list', $hotel_list, false );
		}
		
		// Create a list of villages
		$village_list = $this->get_villages_from_rows( $rows );
		
		// Save the village list
		if ( $village_list ) {
			update_option( 'ah_village_list', $village_list, false );
		}
		
		// Save the last sync date
		update_option( 'ah_hotels_and_villages_last_sync', current_time('Y-m-d H:i:s'), false );
		
		return array( 'hotel_list' => $hotel_list, 'village_list' => $village_list );
	}
	
	/**
	 * Get an array of hotels from the given rows
	 *
	 * @param array $rows
	 *
	 * @return array
	 */
	public function get_hotels_from_rows( $rows ) {
		$hotels = array();
		
		foreach( $rows as $row ) {
			// By using hotel_id as the key, duplicate hotels get removed automatically
			$hotels[ $row['hotel_id'] ] = array(
				'smartsheet_id'   => $row['hotel_id'],
				'smartsheet_name' => $row['hotel_name'],
				'village_id'      => $row['village_id'],
				'village_name'    => $row['village_name'],
				'village_code'    => $row['village_code'],
				'region'          => $row['region'],
				'proprietor_name' => $row['proprietor_name'],
				'email'           => $row['email'],
				'phone'           => $row['phone'],
			);
		}
		
		// Sort by name
		if ( $hotels ) $hotels = ah_sort_by_key( $hotels, 'smartsheet_name' );
		
		return $hotels;
	}
	
	/**
	 * Get an array of villages from the given rows
	 *
	 * @param array $rows
	 *
	 * @return array
	 */
	public function get_villages_from_rows( $rows ) {
		$villages = array();
		
		foreach( $rows as $row ) {
			// By using village_id as the key, duplicate villages get removed automatically
			$villages[ $row['village_id'] ] = array(
				'smartsheet_name' => $row['village_name'],
				'smartsheet_id'   => $row['village_id'],
				'region'          => $row['region'],
				'village_code'    => $row['village_code'],
			);
		}
		
		// Sort by name
		if ( $villages ) $villages = ah_sort_by_key( $villages, 'smartsheet_name' );
		
		return $villages;
	}
	
	/**
	 * Get the village name (minus the region code at the end) from a row
	 *
	 * @param string $village_id
	 *
	 * @return string[]
	 */
	public function split_village_id( $village_id ) {
		$split = explode(' - ', $village_id );
		
		if ( count($split) > 1 ) {
			$code = array_pop($split);
		}else{
			$code = '';
		}
		
		$name = implode(' ', $split);
		
		return array( $name, $code );
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
	 *     @type string $smartsheet_name    "Hotel Schonegg"
	 *     @type string $smartsheet_id      "Hotel Schonegg | Wengen"
	 *     @type string $region             "CH"
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
		update_post_meta( $post_id, 'smartsheet_region', $data['region'] ?? '' );
		
		// Last sync date
		update_post_meta( $post_id, 'smartsheet_last_sync', current_time('Y-m-d H:i:s') );
		
		// Village fields
		if ( $type == 'village' ) {
			update_post_meta( $post_id, 'village_name', $smartsheet_name );
		}
		
		// Hotel fields
		if ( $type == 'hotel' ) {
			$village_id = $data['village_id'] ?? '';
			$village_name = $data['village_name'] ?? '';
			update_post_meta( $post_id, 'smartsheet_village_id', $village_id );
			update_post_meta( $post_id, 'smartsheet_village_name', $village_name );
			
			$village_post_id = $this->get_village_by_smartsheet_id( $village_id );
			
			update_post_meta( $post_id, 'village', $village_post_id ?: '' );
			update_post_meta( $post_id, 'hotel_name', $smartsheet_name );
			update_post_meta( $post_id, 'proprietor_name', $data['proprietor_name'] ?? '' );
			update_post_meta( $post_id, 'email', $data['email'] ?? '' );
			update_post_meta( $post_id, 'phone', $data['phone'] ?? '' );
		}
		
		return true;
	}
	
}