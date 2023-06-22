<?php

class Class_AH_Theme {
	
	public $notices = array();
	
	public function __construct() {
	
		// Displays notices wherever this action is called
		add_action( 'ah_display_notices', array( $this, 'display_notices' ) );
		
		// Display specific notices based on url query args
		add_action( 'ah_display_notices', array( $this, 'add_notices_from_url' ) );
		
		// Log out the user when visiting an ?ah_logout url.
		add_action( 'template_redirect', array( $this, 'maybe_log_out_user' ) );
		
	}
	
	public function load_template( $template_path, $vars = null ) {
		
		// Check the action from a query var in the URL. See rewrites.php for details:
		// 1. "download" = Generate PDF
		// 2. "preview" = Generate PDF, but display as html pages
		// 3. Else generate a regular web page
		$action = get_query_var( 'ah_action' );
		
		$use_pdf = ( $action == 'preview' || $action == 'download' );
		$use_preview = ( $action == 'preview' );
		
		AH_PDF()->use_pdf = $use_pdf;
		AH_PDF()->use_preview = $use_preview;
		
		// Expand variables to be usable in the template
		if ( $vars !== null ) extract( $vars );
		
		if ( $use_pdf ) {
			
			$title = get_the_title();
			$pdf_title = get_the_title();
			
			// Get HTML for the PDF
			ob_start();
			include( AH_PATH . '/templates/parts/pdf-header.php' );
			include( $template_path );
			include( AH_PATH . '/templates/parts/pdf-footer.php' );
			$html = ob_get_clean();
			
			// Generate PDF
			AH_PDF()->generate_from_html( $html, $pdf_title, null, false );
			exit;
			
		}else{
		
			// Enqueue CSS
			wp_enqueue_style( 'ah-pdf-shared', ah_get_asset_url( 'pdf-shared.css' ) );
			wp_enqueue_style( 'ah-pdf-theme', ah_get_asset_url( 'pdf-theme.css' ) );
			
			// Get HTML as a regular page
			include( AH_PATH . '/templates/parts/itinerary-header.php' );
			include( $template_path );
			include( AH_PATH . '/templates/parts/itinerary-footer.php' );
			
		}
	}
	
	/**
	 *
	 *
	 * @return void
	 */
	public function add_notices_from_url() {
		if ( isset($_GET['ah_logged_out']) ) {
			$this->add_notice( 'success', 'You have been logged out successfully.' );
		}
	}
	
	
	/**
	 * Notices: Display, add, get, delete, and delete via ajax
	 *
	 * @return void
	 */
	public function display_notices() {
		$notices = $this->get_notices();
		
		if ( $notices ) foreach( $notices as $key => $notice ) {
			$this->display_notice( $notice, $key );
			$this->remove_notice( $key );
		}
	}
	
	/**
	 * Displays a single notice
	 *
	 * @param $notice
	 * @param $key
	 *
	 * @return void
	 */
	public function display_notice( $notice, $key = false ) {
		$type = $notice['type'];
		$message = $notice['message'];
		$data = $notice['data'];
		$class = $notice['class'];
		
		if ( !$type && !$message ) return;
		
		echo '<div class="ah-theme-notice notice notice-'. esc_attr($type) .' '. esc_attr($class) .'">';
		
		echo '<div class="ah-notice-content">';
		echo wpautop($message);
		
		if ( $data ) {
			$data_id = uniqid();
			echo '<p><a href="#" class="ah-toggle button button-secondary" data-target="#notice-'. esc_attr($data_id) .'">Show Data</a></p>';
			echo '<pre class="code" id="notice-'. esc_attr($data_id) .'" style="display: none;">';
			var_dump($data);
			echo '</pre>';
		}
		echo '</div>';
		
		echo '<a href="#" class="ah-admin-notice-dismiss ah-theme-dismiss"><span>&times;</span></a>';
		
		echo '</div>';
	}
	
	/**
	 * Adds a notice to be displayed on the front-end to the current user.
	 *
	 * @param $type         string      - success, info, message, error
	 * @param $message      string
	 * @param $data         mixed
	 * @param $unique_key   null|string - If provided, only one notice using this key will be stored (latest replaces previous)
	 * @param $class        string      - Custom class string to add to the notice container
	 * @param $display      bool        - If true, the notice will be displayed immediately
	 *
	 * @return void
	 */
	public function add_notice( $type, $message, $data = array(), $unique_key = null, $class = '', $display = false ) {
		$key = uniqid();
		if ( $unique_key !== null ) $key = $unique_key;
		
		$notice = compact( 'type', 'message', 'data', 'class');
		
		// If notices have already been sent, display this one immediately
		if ( did_action( 'ah_display_notices' ) ) {
			$display = true;
		}
		
		// Should this notice be displayed immediately?
		if ( $display ) {
			
			// Display the notice right now
			$this->display_notice( $notice );
			
		}else{
		
			// Store to be displayed later
			$notices = $this->get_notices();
			$notices[$key] = $notice;
			$this->notices = $notices;
			
			if ( did_action( 'ah_display_notices' ) ) {
				do_action( 'ah_display_notices' );
			}
			
		}
	}
	
	public function get_notices() {
		return $this->notices;
	}
	
	public function remove_notice( $key ) {
		$notices = $this->get_notices();
		
		if ( isset($notices[$key]) ) {
			unset($notices[$key]);
			$this->notices = $notices;
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Make the user log out when visiting a url with ?ah_logout or /logout/
	 *
	 * @return void
	 */
	public function maybe_log_out_user() {
		if ( ! isset($_GET['ah_logout']) && ! str_starts_with( $_SERVER['REQUEST_URI'], '/logout/' ) ) return;
		
		// Determine where the user should go after being logged out
		if ( isset($_GET['redirect_url']) ) {
			$redirect_url = stripslashes($_GET['redirect_url']);
			$redirect_url = remove_query_arg( 'ah_logout', $redirect_url );
		}else{
			$redirect_url = site_url('/');
		}
		
		// Log out
		if ( is_user_logged_in() ) {
			$redirect_url = add_query_arg( array('ah_logged_out' => 1), $redirect_url );
			wp_logout();
		}
		
		// Redirect
		wp_safe_redirect($redirect_url);
		exit;
	}
	
}