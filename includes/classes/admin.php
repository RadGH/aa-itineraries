<?php

class Class_AH_Admin {
	
	public function __construct() {
		
		// Register ACF settings pages
		add_action( 'admin_menu', array( $this, 'register_menus' ), 20 );
		
		// Register custom image sizes
		add_action( 'after_setup_theme', array( $this, 'register_image_sizes' ) );
		
		// Displays errors on the account dashboard
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
		
		// Allows clearing a notice
		add_action( 'admin_init', array( $this, 'ajax_remove_notice' ) );
		
		// Add a test notice
		// https://alpinehikerdev.wpengine.com/?ah_test_notice
		if ( isset($_GET['ah_test_notice']) ) add_action( 'init', array( $this, 'ah_test_notice' ) );
	}
	
	public function ah_test_notice() {
		$type = 'success';
		$message = 'Hello world!' . "\n\n" . 'This is a message.';
		$data = array( 'first_name' => 'Radley', 'last_name' => 'Sustaire' );
		$this->add_notice( $type, $message, $data );
		echo 'notice added';
		exit;
	}
	
	public function register_menus() {
		if ( function_exists('acf_add_options_page') ) {
			
			// Account Pages -> Settings
			acf_add_options_page(array(
				'page_title' 	=> 'Account Page Settings (ah_account_page)',
				'menu_title' 	=> 'Settings',
				'parent_slug' 	=> 'edit.php?post_type=ah_account_page',
				'post_id'       => 'ah_account_page',
				'slug'          => 'acf-ah-account-page',
				'autoload'      => false,
				'capability'    => 'manage_options',
			));
			
			// Invoices -> Settings
			acf_add_options_page(array(
				'page_title' 	=> 'Invoice Settings (ah_invoices)',
				'menu_title' 	=> 'Settings',
				'parent_slug' 	=> 'edit.php?post_type=ah_invoice',
				'post_id'       => 'ah_invoices',
				'slug'          => 'acf-ah-invoices',
				'autoload'      => false,
				'capability'    => 'manage_options',
			));
			
		}
	}
	
	public function register_image_sizes() {
		add_image_size( 'document-preview', 300, 300, false );
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
			
			if ( !$type && !$message ) {
				$this->remove_notice( $key );
				continue;
			}
			
			echo '<div class="ah-admin-notice notice notice-'. $type .' "">';
			
			echo wpautop($message);
			
			if ( $data ) {
				echo '<pre class="code">';
				var_dump($data);
				echo '</pre>';
			}
			
			$url = add_query_arg(array( 'ah-notice-dismiss' => $key, 'ah-ajax' => 0 ));
			echo '<a href="'. esc_attr($url) .'" class="ah-admin-notice-dismiss notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>';
			
			echo '</div>';
		}
	}
	
	public function add_notice( $type, $message, $data = array() ) {
		$notices = $this->get_notices();
		$key = uniqid();
		$notices[$key] = compact( 'type', 'message', 'data' );
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
	
}