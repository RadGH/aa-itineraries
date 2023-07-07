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
		
	}
	
	public function generate_from_html( $html, $title, $filename = null, $force_download = false ) {
		
		$this->use_pdf = true;
		
		// Disable image srcset (breaks mpdf)
		add_filter( 'max_srcset_image_width', '__return_false' );
		add_filter( 'wp_calculate_image_srcset', '__return_false' );
		add_filter( 'intermediate_image_sizes_advanced', '__return_false' );
		
		// Generate the PDF
		$this->generate_with_mpdf( $html, $title, $filename, $force_download ); // old method
	}
	
	public function format_pdf_title( $title ) {
		if ( !$title ) $title = get_bloginfo( 'name' );
		
		$title = wp_strip_all_tags( $title );
		
		return $title;
	}
	
	public function format_pdf_filename( $filename, $title ) {
		if ( !$filename ) $filename = $title;
		if ( !$filename ) $filename = get_bloginfo( 'name' );
		
		$filename = wp_strip_all_tags( $filename );
		$filename = str_replace(array('–', '—'), '-', $filename ); // ndash and mdash to hyphen
		$filename = preg_replace('/[^a-zA-Z0-9\-\_ ]+/', '', $filename);
		$filename .= '.pdf';
		
		return $filename;
	}
	
	// Create a PDF from HTML using MPDF
	private function generate_with_mpdf( $html, $title = '', $filename = '', $force_download = false ) {
		$title = $this->format_pdf_title( $title );
		$filename = $this->format_pdf_filename( $filename, $title );
		
		$this->pdf = $this->create_mpdf();
		
		// Set document title
		$this->pdf->SetTitle( $title );
		
		// Add CSS from pdf.css
		$this->add_mpdf_stylesheet();
		
		// Write HTML
		$this->pdf->WriteHTML($html);
		
		// Finish
		$this->send_mpdf( $filename, $force_download );
		
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
					'montserrat' => array(
						'R' => 'montserrat-regular.ttf',
						'I' => 'montserrat-italic.ttf',
						'B' => 'montserrat-bold.ttf',
						'BI' => 'montserrat-bold-italic.ttf',
					),
					'ammer-handwriting' => array(
						'R' => 'ammer-handwriting-regular.ttf',
					),
				),
			
			// Page settings
			'orientation' => 'P',
			
			
			// DPI's
			// standard: 72
			// mpdf defualt: 96 (25% more)
			// print: 300
			// 1628 / 1222
			'dpi' => 96,
			'img_dpi' => 96, // default 96
			
			'format' => 'LETTER',
			// 'format' => [$w = 210, $w * (11/8.5)],
			// 815px x 1055px
			
			// 595pt @ 210mm = 2.8333pt per mm
			// 815px / 2.8333
			
			// Margins
			/*
			'margin_left'   => 16,
			'margin_right'  => 16,
			'margin_top'    => 12,
			'margin_bottom' => 12,
			'margin_header' => 12,
			'margin_footer' => 12,
			*/
			
			'margin_left'   => 12,
			'margin_right'  => 12,
			
			'margin_top'    => 12,
			'margin_header' => 0, // not used yet
			
			'margin_bottom' => 18, // changes the page size, does not effect the footer
			'margin_footer' => 12, // moves the footer, but does not change the page size
			
		));
	}
	
	// Sends headers and then streams PDF to the browser
	public function send_mpdf( $filename = 'document.pdf', $force_download = false ) {
		
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
		// D = force download
		// I = stream inline (to browser)
		$this->pdf->Output( $filename, ($force_download ? 'D' : 'I') );
		exit;
		
	}
	
	// Add stylesheet to the PDF (embedded directly, not a link)
	public function add_mpdf_stylesheet() {
		$path = realpath(__DIR__ . '/../../assets/pdf.css');
		if ( file_exists($path) ) {
			$stylesheet = file_get_contents($path);
			$this->pdf->WriteHTML($stylesheet, 1); // The parameter 1 tells that this is css/style only and no body/html/text
		}
		
		// Add the editor styles
		// This adds support for classes like "color-navy" which are available in the Formats dropdown of the visual editor
		$path = get_template_directory() . '/_static/styles/_admin/editor-styles.min.css';
		if ( file_exists($path) ) {
			$stylesheet = file_get_contents($path);
			$this->pdf->WriteHTML($stylesheet, 1);
		}
	}
	
}