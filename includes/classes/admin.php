<?php

class Class_AH_Admin {
	
	public function __construct() {
		
		// Register ACF settings pages
		add_action( 'acf/init', array( $this, 'register_admin_menus' ), 20 );
		
		// Register custom image sizes
		add_action( 'after_setup_theme', array( $this, 'register_image_sizes' ) );
		
		// Change the "big image size" threshhold to match the largest itinerary image size (2560px)
		add_filter('big_image_size_threshold', array( $this, 'custom_big_image_threshold' ), 10, 4);
		
		// Displays errors on the account dashboard
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
		
		// Show warning that Theme Settings page is different than Alpine Hiker's theme settings
		add_action( 'admin_notices', array( $this, 'display_theme_setting_warning' ) );
		
		// Allows clearing a notice
		add_action( 'admin_init', array( $this, 'ajax_remove_notice' ) );
		
		// Loads notices from the query arg "ah_notice"
		add_action( 'admin_init', array( $this, 'prepare_url_notices' ) );
		
		// Ajax: Search smartsheet spreadsheets
		add_action( 'wp_ajax_ah_search_spreadsheets', array( $this, 'ajax_ah_search_spreadsheets' ) );
		
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
			
			acf_add_options_sub_page( array(
				'parent_slug' => 'acf-ah-settings-parent',
				'menu_slug'   => 'acf-ah-email-settings',
				'page_title'  => 'Email Settings (ah_emails)',
				'menu_title'  => 'Email Settings',
				'capability'  => 'manage_options',
				'post_id'     => 'ah_emails',
				'autoload'      => false,
			) );
			
		}
		
		// redirect for old menu
		$page = $_GET['page'] ?? false;
		if ( $page === 'acf-ah-smartsheet' ) {
			$url = add_query_arg(array('page' => 'acf-ah-settings'));
			wp_redirect( $url );
			exit;
		}
	}
	
	public function register_image_sizes() {
		add_image_size( 'document-preview', 300, 300, false );
		add_image_size( 'document-embed', 650, 900, false );
		
		// Image size for PDFs at 300dpi (8.5" wide)
		add_image_size( 'itinerary-pdf', 2560, 0, false );
		
		// Image size for web (850px content width)
		add_image_size( 'itinerary-web', 850, 0, false );
	}
	
	/**
	 * Set the max image size of "big images" so that the width is 2560px max
	 *
	 * @param int    $threshold     The threshold value in pixels. Default 2560.
	 * @param array  $imagesize     {
	 *     Indexed array of the image width and height in pixels.
	 *
	 *     @type int $0 The image width.
	 *     @type int $1 The image height.
	 * }
	 * @param string $file          Full path to the uploaded image file.
	 * @param int    $attachment_id Attachment post ID.
	 *
	 * @return int
	 */
	public function custom_big_image_threshold($threshold, $imagesize, $file, $attachment_id) {
		$target_w = 2560;
		
		$w = $imagesize[0];
		$h = $imagesize[1];
		
		if ( $h > $w ) {
			// If the image is portrait (tall), adjust the height proportionally so the width remains at 2560
			return ceil( $h * ( $target_w / $w ) );
		}
		
		return $target_w;
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
		
		$data = isset($_GET['ah_notice_data']) ? stripslashes_deep($_GET['ah_notice_data']) : false;
		if ( $data && is_string($data) ) {
			$d = json_decode($data, true);
			if ( $d !== null ) $data = $d;
		}
		
		switch($notice) {
			
			// Sync (generic)
			case 'sync_item_success':
				$this->add_notice( 'success', 'Sync data updated successfully.', null, null, true );
				break;
				
			// Itinerary
			case 'itinerary_loaded_from_template':
				$template_id = $data['template_id'] ?? 0;
				$template_link = sprintf(
					'<a href="%s" target="_blank">%s</a>',
					get_edit_post_link( $template_id ),
					get_the_title( $template_id )
				);
				$this->add_notice( 'success', 'This itinerary has been loaded from template: ' . $template_link, null, null, true );
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
				$this->add_notice( 'success', 'Rooms settings have been updated. Remember to run the sync next!', null, null, true );
				break;
				
			// Meals
			case 'meals_list_updated':
				$this->add_notice( 'success', 'Meals settings have been updated. Remember to run the sync next!', null, null, true );
				break;
			
			case 'sync_rooms_success':
				$rooms_count = $data['rooms'] ?? 0;
				$this->add_notice( 'success', 'Sync complete. Found ' . $rooms_count . ' room types.', null, null, true );
				break;
				
			case 'sync_meals_success':
				$meals_count = $data['meals'] ?? 0;
				$this->add_notice( 'success', 'Sync complete. Found '. $meals_count .' meal types.', null, null, true );
				break;
			
			case 'sync_rooms_failed':
				$this->add_notice( 'success', 'Failed to sync room information from the spreadsheet.', null, null, true );
				break;
				
			case 'sync_meals_failed':
				$this->add_notice( 'success', 'Failed to sync meal information from the spreadsheet.', null, null, true );
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
	
	/**
	 * Ajax: Search smartsheet spreadsheets
	 * See admin.js -> setup_spreadsheet_finder() -> search_spreadsheets()
	 *
	 * @return void
	 */
	/*
	public function ajax_ah_search_spreadsheets() {
		$search_term = stripslashes($_POST['search']);
		if ( empty($search_term) ) {
			echo '';
			exit;
		}
		
		$sheet_list = AH_Smartsheet_Sync_Sheets()->get_stored_sheet_list();
		
		$found_sheets = array();
		
		foreach( $sheet_list as $sheet ) {
			$sheet_name = $sheet['name'];
			$sheet_id = $sheet['id'];
			$sheet_url = $sheet['permalink'];
			
			// Returns the number of matching characters
			$similarity = similar_text( $sheet_name, $search_term );
			
			// If the search term is found in the title exactly, add bonus points to the similarity
			if ( stripos($sheet_name, $search_term) ) {
				$similarity = $similarity + strlen($search_term);
			}
			
			if ( $similarity > 0 ) {
				$found_sheets[] = array(
					'id' => $sheet_id,
					'name' => $sheet_name,
					'url' => $sheet_url,
					'similarity' => $similarity,
				);
			}
		}
		
		if ( ! $found_sheets ) {
			echo '<p>No sheets found with the title "'. esc_html($search_term) .'".</p>';
			exit;
		}
		
		// Order by "similarity" property
		usort( $found_sheets, function( $a, $b ) {
			return $b['similarity'] - $a['similarity'];
		});
		
		echo '<div class="sheet-list">';
		
		// Display a list of sheets. Each with a button to select it.
		foreach( $found_sheets as $i => $sheet ) {
			?>
			<div class="sheet-item">
				<div class="sheet-title"><?php echo esc_html($sheet['name']); ?></div>
				<div class="sheet-button">
					<a href="#" class="button button-small button-primary ah-select-sheet" data-sheet-id="<?php echo esc_attr($sheet['id']); ?>">Select</a>
					<a href="<?php echo esc_attr($sheet['url']); ?>" class="button button-small button-secondary" target="_blank">Open&nbsp;<span class="dashicons dashicons-external"></span></a>
				</div>
			</div>
			<?php
			
			if ( $i > 29 ) {
				break;
			}
		}
		
		echo '</div>';
		
		exit;
	}
	*/
	
}