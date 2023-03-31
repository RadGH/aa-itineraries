<?php

class Class_AH_Theme {
	
	public $notices = array();
	
	public function __construct() {
	
		add_action( 'ah_display_notices', array( $this, 'display_notices' ) );
		
	}
	
	public function load_template( $template_path ) {
		
		// Check the action from a query var in the URL. See rewrites.php for details:
		// 1. "download" = Generate PDF
		// 2. "preview" = Generate PDF, but display as html pages
		// 3. Else generate a regular web page
		$action = get_query_var( 'ah_action' );
		
		$use_pdf = ( $action == 'preview' || $action == 'download' );
		$use_preview = ( $action == 'preview' );
		
		AH_PDF()->use_pdf = $use_pdf;
		AH_PDF()->use_preview = $use_preview;
		
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
			AH_PDF()->generate_from_html( $html, $pdf_title, null, true );
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
			
			echo '<div class="ah-theme-notice notice notice-'. $type .' ">';
			
			echo '<div class="ah-notice-content">';
			echo wpautop($message);
			
			if ( $data ) {
				echo '<p><a href="#" class="ah-toggle button button-secondary" data-target="#notice-'. $key .'">Show Data</a></p>';
				echo '<pre class="code" id="notice-'. $key .'" style="display: none;">';
				var_dump($data);
				echo '</pre>';
			}
			echo '</div>';
			
			echo '<a href="#" class="ah-admin-notice-dismiss ah-theme-dismiss"><span>&times;</span></a>';
			
			echo '</div>';
		}
	}
	
	/**
	 * Adds a notice to be displayed on the front-end to the current user.
	 *
	 * @param $type         string      - success, info, message, error
	 * @param $message      string
	 * @param $data         mixed
	 * @param $unique_key   null|string - If provided, only one notice using this key will be stored (latest replaces previous)
	 *
	 * @return void
	 */
	public function add_notice( $type, $message, $data = array(), $unique_key = null ) {
		$key = uniqid();
		if ( $unique_key !== null ) $key = $unique_key;
		
		$notices = $this->get_notices();
		$notices[$key] = compact( 'type', 'message', 'data');
		$this->notices = $notices;
		
		if ( did_action( 'ah_display_notices' ) ) {
			do_action( 'ah_display_notices' );
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
	
}