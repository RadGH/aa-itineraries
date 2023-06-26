<?php

class Class_AH_Admin {
	
	public function __construct() {
		
		// Register ACF settings pages
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 20 );
		
		// Register custom image sizes
		add_action( 'after_setup_theme', array( $this, 'register_image_sizes' ) );
		
		// Displays errors on the account dashboard
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
		
		// Show warning that Theme Settings page is different than Alpine Hiker's theme settings
		add_action( 'admin_notices', array( $this, 'display_theme_setting_warning' ) );
		
		// Allows clearing a notice
		add_action( 'admin_init', array( $this, 'ajax_remove_notice' ) );
		
		// Loads notices from the query arg "ah_notice"
		add_action( 'admin_init', array( $this, 'prepare_url_notices' ) );
		
	}
	
	public function register_admin_menus() {
		if ( function_exists('acf_add_options_page') ) {
			
			// Account Pages -> Settings
			/*
			acf_add_options_page(array(
				'page_title' 	=> 'Account Page Settings (ah_account_page)',
				'menu_title' 	=> 'Settings',
				'parent_slug' 	=> 'edit.php?post_type=ah_account_page',
				'post_id'       => 'ah_account_page',
				'slug'          => 'acf-ah-account-page',
				'autoload'      => false,
				'capability'    => 'manage_options',
			));
			*/
			
			// Alpine Hikers Settings
			acf_add_options_page(array(
				'menu_slug'     => 'acf-ah-settings-parent',
				'page_title' 	=> null, // 'Alpine Hikers (ah_settings)',
				'menu_title' 	=> 'Alpine Hikers Settings',
				'post_id'       => null, //'ah_settings',
				'autoload'      => false,
				'capability'    => 'manage_options',
				'icon_url'      => 'dashicons-alpine-hikers',
				'redirect' 		=> true, // True = Use first sub-page instead
			));
			
			// Alpine Hikers Settings -> General Settings (Duplicate of the above)
			acf_add_options_sub_page( array(
				'parent_slug' => 'acf-ah-settings-parent',
				'menu_slug'   => 'acf-ah-settings',
				'page_title'  => 'General Settings (ah_settings)',
				'menu_title'  => 'General Settings',
				'capability'  => 'manage_options',
				'post_id'     => 'ah_settings',
				'autoload'      => false,
			) );
			
		}
	}
	
	public function register_image_sizes() {
		add_image_size( 'document-preview', 300, 300, false );
		add_image_size( 'document-embed', 650, 900, false );
	}
	
	/**
	 * Notices: Display, add, get, delete, and delete via ajax
	 *
	 * @return void
	 */
	public function display_notices() {
		$notices = $this->get_notices();
		
		if ( $notices ) foreach( $notices as $key => $n ) {
			$type = $n['type'];
			$message = $n['message'];
			$data = $n['data'];
			$date = $n['date'];
			$auto_dismiss = $n['auto_dismiss'];
			
			if ( !$type && !$message ) {
				$this->remove_notice( $key );
				continue;
			}
			
			if ( $auto_dismiss ) {
				$this->remove_notice( $key );
			}
			
			if ( $message && $date ) $message .= "\n\n";
			if ( $date && !$auto_dismiss ) $message .= '<em>' . human_time_diff(strtotime($date), current_time('timestamp')) . ' ago</em>';
			
			echo '<div class="ah-admin-notice notice notice-'. $type .' '. ($auto_dismiss ? 'ah-auto-dismiss' : 'ah-ajax-dismiss') .'">';
			
			echo wpautop($message);
			
			if ( $data ) {
				echo '<p><a href="#" class="ah-toggle button button-secondary" data-target="#notice-'. $key .'">Show Data</a></p>';
				echo '<pre class="code" id="notice-'. $key .'" style="display: none;">';
				var_dump($data);
				echo '</pre>';
			}
			
			$url = add_query_arg(array( 'ah-notice-dismiss' => $key, 'ah-ajax' => 0 ));
			echo '<a href="'. esc_attr($url) .'" class="ah-admin-notice-dismiss notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>';
			
			echo '</div>';
		}
	}
	
	/**
	 * Show warning that Theme Settings page is different than Alpine Hiker's theme settings
	 *
	 * @return void
	 */
	public function display_theme_setting_warning() {
		if ( ! acf_is_screen( 'theme-general-settings' ) ) return;
		
		echo 1;
		exit;
	}
	
	/**
	 * @param $type         string      - success, info, message, error
	 * @param $message      string
	 * @param $data         mixed
	 * @param $unique_key   null|string - If provided, only one notice using this key will be stored (latest replaces previous)
	 * @param $auto_dismiss bool        - If true, will only be displayed once
	 *
	 * @return void
	 */
	public function add_notice( $type, $message, $data = array(), $unique_key = null, $auto_dismiss = false ) {
		$date = current_time('Y-m-d G:i:s');
		
		$key = uniqid();
		if ( $unique_key !== null ) $key = $unique_key;
		
		$notices = $this->get_notices();
		$notices[$key] = compact( 'type', 'message', 'data', 'date', 'auto_dismiss' );
		update_option( 'ah-admin-notice', $notices, false );
	}
	
	public function get_notices() {
		return (array) get_option( 'ah-admin-notice' );
	}
	
	public function remove_notice( $key ) {
		$notices = $this->get_notices();
		
		if ( isset($notices[$key]) ) {
			unset($notices[$key]);
			update_option( 'ah-admin-notice', $notices);
			return true;
		}else{
			return false;
		}
	}
	
	public function ajax_remove_notice() {
		if ( isset($_GET['ah-notice-dismiss']) ) {
			$key = stripslashes($_GET['ah-notice-dismiss']);
			$this->remove_notice( $key );
			
			if ( isset($_GET['ah-ajax']) ) {
				echo json_encode(array('success' => 1));
				exit;
			}else{
				$url = remove_query_arg( 'ah-notice-dismiss' );
				$url = remove_query_arg( 'ah-ajax', $url );
				wp_redirect( $url );
				exit;
			}
		}
	}
	
	public function prepare_url_notices() {
		if ( ! isset($_GET['ah_notice']) ) return;
		
		$notice = stripslashes($_GET['ah_notice']);
		$data = isset($_GET['ah_notice_data']) ? stripslashes($_GET['ah_notice_data']) : false;
		if ( $data ) $data = json_decode($data, true);
		
		switch($notice) {
			
			// Sync (generic)
			case 'sync_item_success':
				$this->add_notice( 'success', 'Sync data updated successfully.', null, null, true );
				break;
				
			// Villages and Hotels
			case 'hotel_list_updated':
				$this->add_notice( 'success', 'Village and Hotel settings have been updated. Remember to run the sync next!', null, null, true );
				break;
				
			case 'sync_hotels_success':
				$hotel_count = $data['hotels'] ?? 0;
				$village_count = $data['villages'] ?? 0;
				$this->add_notice( 'success', 'Sync complete. Found '. $village_count . ' villages and '. $hotel_count .' hotels.', null, null, true );
				break;
				
			case 'sync_hotels_failed':
				$this->add_notice( 'success', 'Failed to sync hotel and village information from the spreadsheet.', null, null, true );
				break;
			
			case 'smartsheet_village_inserted':
				$this->add_notice( 'success', 'This village has been automatically created and paired with Smartsheet.', null, null, true );
				break;
			
			case 'smartsheet_hotel_inserted':
				$this->add_notice( 'success', 'This hotel has been automatically created and paired with Smartsheet.', null, null, true );
				break;
			
			case 'smartsheet_village_sync_complete':
				$this->add_notice( 'success', 'The village information has been updated to match Smartsheet.', null, null, true );
				break;
			
			case 'smartsheet_hotel_sync_complete':
				$this->add_notice( 'success', 'The hotel information has been updated to match Smartsheet.', null, null, true );
				break;
			
			// Hikes
			case 'hike_list_updated':
				$this->add_notice( 'success', 'Hike settings have been updated. Remember to run the sync next!', null, null, true );
				break;
				
			case 'sync_hikes_success':
				$hotel_count = $data['hikes'] ?? 0;
				$this->add_notice( 'success', 'Sync complete. Found '. $hotel_count . ' hikes.', null, null, true );
				break;
				
			case 'sync_hikes_failed':
				$this->add_notice( 'success', 'Failed to sync hikes from the spreadsheet.', null, null, true );
				break;
			
			case 'smartsheet_hike_inserted':
				$this->add_notice( 'success', 'This hike has been automatically created and paired with Smartsheet.', null, null, true );
				break;
				
			case 'smartsheet_hike_sync_complete':
				$this->add_notice( 'success', 'The hike information has been updated to match Smartsheet.', null, null, true );
				break;
			
			// Rooms
			case 'room_list_updated':
				$this->add_notice( 'success', 'Rooms and Meals settings have been updated. Remember to run the sync next!', null, null, true );
				break;
			
			case 'sync_rooms_success':
				$rooms_count = $data['rooms'] ?? 0;
				$meals_count = $data['meals'] ?? 0;
				$this->add_notice( 'success', 'Sync complete. Found ' . $rooms_count . ' room types and '. $meals_count .' meal types.', null, null, true );
				break;
			
			case 'sync_rooms_failed':
				$this->add_notice( 'success', 'Failed to sync room and meal information from the spreadsheet.', null, null, true );
				break;
			
			// Sync, generic
			case 'sync_sheets_failed':
				$this->add_notice( 'success', 'Failed to sync list of sheets from Smartsheet.', null, null, true );
				break;
			
			case 'sync_sheets_success':
				$sheet_count = $data['sheets'] ?? 0;
				$this->add_notice( 'success', 'Sync complete. Found ' . $sheet_count . ' sheets.', null, null, true );
				break;
				
			default:
				$this->add_notice( 'error', 'Unsupported notice type: "'. esc_html($notice) .'"', null, null, true );
				break;
				
		}
	}
	
}