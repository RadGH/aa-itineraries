<?php

if ( function_exists( 'opcache_invalidate') ) {
	opcache_invalidate( __FILE__ );
}


class Class_AH_PDF {
	
	/** @var Mpdf\Mpdf $pdf */
	public $pdf = null;
	
	public $use_pdf = false;
	public $use_preview = false;
	
	public function __construct() {
		
		// Preview pdf with ?previewpdf in the URL
		// Can be set externally, see theme.php -> load_template() for example
		if ( isset($_GET['previewpdf']) ) {
			$this->use_preview = true;
		}
		
		// https://alpinehikerdev.wpengine.com/?preview_pdf_fonts
		if ( isset($_GET['preview_pdf_fonts']) ) {
			add_action( 'init', array( $this, 'preview_pdf_fonts' ) );
		}
		
		// https://alpinehikerdev.wpengine.com/?preview_test_page
		if ( isset($_GET['preview_test_page']) ) {
			add_action( 'init', array( $this, 'preview_test_page' ) );
		}
		
	}
	
	// https://alpinehikerdev.wpengine.com/?preview_pdf_fonts
	public function preview_pdf_fonts() {
		$html = file_get_contents( AH_PATH . '/assets/fonts/fonts.html' );
		
		$this->generate_with_wkhtmltopdf_api( $html );
	}
	
	// https://alpinehikerdev.wpengine.com/?preview_test_page
	public function preview_test_page() {
		$html = file_get_contents( AH_PATH . '/assets/fonts/test.html' );
		
		$this->generate_with_wkhtmltopdf_api( $html );
	}
	
	public function generate_from_html( $html, $title, $filename = null, $force_download = false ) {
		
		$this->use_pdf = true;
		
		// Disable image srcset (breaks mpdf)
		add_filter( 'max_srcset_image_width', '__return_false' );
		add_filter( 'wp_calculate_image_srcset', '__return_false' );
		add_filter( 'intermediate_image_sizes_advanced', '__return_false' );
		
		// Generate the PDF
		// $this->generate_with_wkhtmltopdf_api( $html, $title, $filename, $force_download );
		$this->generate_with_mpdf( $html, $title, $filename, $force_download ); // old method
	}
	
	private function generate_with_wkhtmltopdf_api( $html, $title = '', $filename = '', $force_download = false ) {
		
		// Preview as HTML
		if ( $this->use_preview ) {
			echo $html;
			exit;
		}
		
		$title = $this->format_pdf_title( $title );
		
		$filename = $this->format_pdf_filename( $filename, $title );
		
		$url = 'https://wkhtmltopdf.radgh.com/';
		
		$wkhtml_args = array(
			'orientation' => 'portrait',
		);
		
		$headers = array();
		
		$post_fields = array(
			'html' => $html,
			'args' => $wkhtml_args,
			'filename' => $filename,
		);
		
		$args = array(
			'headers' => $headers,
			'body' => $post_fields,
			'timeout' => 30,
		);
		
		$response = wp_remote_post( $url, $args );
		
		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$body = wp_remote_retrieve_body( $response );
			
			$mime = 'application/pdf';
			$filesize = mb_strlen($body, '8bit'); // 8-bit required for filesize instead of string length
			
			ob_clean();
			
			header( "Content-type: " . $mime, true, 200 );
			header( "Content-Transfer-Encoding: Binary" );
			header( "Content-length: " . $filesize );
			header( "Pragma: no-cache" );
			header( "Expires: 0" );
			header( "Cache-Control: no-store, no-cache, must-revalidate" );
			header( "Cache-Control: post-check=0, pre-check=0", false );
			
			if ( $force_download ) {
				header( "Content-disposition: attachment;filename=" . esc_attr($filename) );
			}else{
				header( "Content-disposition: inline;filename=" . esc_attr($filename) );
			}
			
			echo $body;
			exit();
		}
		
		pre_dump($response);
		exit;
	}
	
	public function format_pdf_title( $title ) {
		
		$title = wp_strip_all_tags( $title );
		
		return $title;
	}
	
	public function format_pdf_filename( $filename, $title ) {
		if ( empty($filename) ) $filename = $title;
		
		$filename = wp_strip_all_tags( $filename );
		$filename = preg_replace('/[^a-zA-Z0-9\-\_ ]+/', '', $filename);
		$filename .= '.pdf';
		
		return $filename;
	}
	
	// Create a PDF from HTML using MPDF
	private function generate_with_mpdf( $html, $title, $filename, $force_download ) {
		$this->pdf = $this->create_mpdf();
		
		// Set document title
		$this->pdf->SetTitle( $this->document_title );
		
		// Add CSS from pdf.css
		$this->add_mpdf_stylesheet();
		
		// Write HTML
		$this->pdf->WriteHTML($html);
		
		// Finish
		$this->send_mpdf();
		
		exit;
	}
	
	// Creates the PDF object with custom settings applied
	public function create_mpdf() {
		require_once( __DIR__ . '/vendor/autoload.php' );
		
		// Use default configs
		// $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
		// $fontDirs = $defaultConfig['fontDir'];
		// $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
		// $fontData = $defaultFontConfig['fontdata'];
	
		$fontDirs = array();
		$fontData = array();
		
		$path = realpath( __DIR__ . '/../../assets/fonts/' );
		
		if ( $this->use_preview ) {
			return new Class_AH_PDF_Preview();
		}
		
		// Create PDF object
		return new \Mpdf\Mpdf(array(
			
			// Fonts
			'default_font_size' => 16,
			// 'default_font' => 'freight-text-pro',
			'default_font' => 'fraunces',
			'fontDir' => array_merge($fontDirs, array($path)),
			'fontdata' => $fontData +
				array(
					'fraunces' => array(
						'R' => 'fraunces-regular.ttf',
						'I' => 'fraunces-italic.ttf',
						'B' => 'fraunces-bold.ttf',
						'BI' => 'fraunces-bold-italic.ttf',
					),
					'freight-text-pro' => array(
						'R' => 'freight-text-pro-regular.ttf',
						'I' => 'freight-text-pro-italic.ttf',
						'B' => 'freight-text-pro-bold.ttf',
						'BI' => 'freight-text-pro-bold-italic.ttf',
					),
					'blkad' => array(
						'R' => 'blkad.ttf',
					),
				),
			
			// Page settings
			'orientation' => 'P',
			
			'format' => 'LETTER',
			// 'format' => [$w = 210, $w * (11/8.5)],
			// 815px x 1055px
			
			// 595pt @ 210mm = 2.8333pt per mm
			// 815px / 2.8333
			
			// Margins
			/*
			'margin_left'   => 0, // 15,
			'margin_right'  => 0, // 15,
			'margin_top'    => 0, // 16,
			'margin_bottom' => 0, // 16,
			'margin_header' => 0, // 9,
			'margin_footer' => 0, // 9,
			*/
			
			'margin_left'   => 16,
			'margin_right'  => 16,
			'margin_top'    => 12,
			'margin_bottom' => 12,
			'margin_header' => 12,
			'margin_footer' => 12,
			
		));
	}
	
	// Sends headers and then streams PDF to the browser
	public function send_mpdf() {
		
		// Clear output buffer - Without this the PDF fails to load.
		ob_end_clean();
		
		// Send headers informing browser that this is a PDF
		// 1. Do not put PDF on Google
		header( "X-Robots-Tag: noindex, nofollow" );
		
		// 2. Do not cache PDF
		header( "Pragma: no-cache" );
		header( "Expires: 0" );
		header( "Cache-Control: no-store, no-cache, must-revalidate" );
		header( "Cache-Control: post-check=0, pre-check=0", false );
		
		// Send PDF to browser
		$this->pdf->Output( $this->filename, 'I' );
		exit;
		
	}
	
	// Add stylesheet to the PDF (embedded directly, not a link)
	public function add_mpdf_stylesheet() {
		$stylesheet = file_get_contents(__DIR__ . '/../../assets/pdf.css');
		$this->pdf->WriteHTML($stylesheet, 1); // The parameter 1 tells that this is css/style only and no body/html/text
	}
	
}