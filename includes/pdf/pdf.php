<?php

if ( function_exists( 'opcache_invalidate') ) {
opcache_invalidate( __FILE__ );
}


class Class_AH_PDF {
	
	/** @var Mpdf\Mpdf $pdf */
	public $pdf = null;
	
	/** @var array $entry */
	public $entry = null;
	
	/** @var array $form */
	public $form = null;
	
	// Other settings
	public $prefix = false;
	public $chart_src = false;
	
	// Result settings
	public $result_title = false;
	public $result_intro = false;
	public $result_next_steps = false;
	public $result_detailed_results = false;
	
	// Entry fields
	public $first_name = false;
	public $last_name = false;
	public $email = false;
	
	// PDF Settings
	public $title = false;
	public $subtitle = false;
	public $copyright = false;
	public $feedback = false;
	public $next_step = false;
	
	// mPDF Settings
	public $document_title = false;
	public $filename = false;
	
	public $use_preview = false;
	
	public function __construct() {
		
		// PDF generation is done using mpdf
		// @see https://mpdf.github.io/installation-setup/installation-v7-x.html
		
		// Display a PDF to a visitor by specifying ?alpine_pdf in the URL with the value of a secret key
		// https://alpinehikerdev.wpengine.com/?alpine_pdf=640b80a562034
		if ( isset($_GET['alpine_pdf']) ) {
			add_action( 'init', array( $this, 'display_pdf_to_visitor' ) );
		}
		
		// Preview pdf with ?previewpdf in the URL
		// Can be set externally, see theme.php -> load_template() for example
		if ( isset($_GET['previewpdf']) ) {
			$this->use_preview = true;
		}
		
	}
	
	public function generate_from_html( $html, $title, $filename = null ) {
		// Create PDF and load settings
		$this->pdf = $this->create_pdf();
		
		$this->document_title = wp_strip_all_tags( $title );
		
		if ( $filename ) {
			$this->filename = $filename;
		}else{
			$this->filename = $this->get_pdf_filename( $this->document_title );
		}
		
		// Disable image srcset (breaks mpdf)
		add_filter( 'max_srcset_image_width', '__return_false' );
		add_filter( 'wp_calculate_image_srcset', '__return_false' );
		add_filter( 'intermediate_image_sizes_advanced', '__return_false' );
		
		// Set document title
		$this->pdf->SetTitle( $this->document_title );
		
		// Add CSS from pdf.css
		$this->add_stylesheet();
		
		// Write HTML
		$this->pdf->WriteHTML($html);
		
		// Finish
		$this->send_pdf();
		
		exit;
	}
	
	// https://alpinehikerdev.wpengine.com/?alpine_pdf=640b80a562034
	public function display_pdf_to_visitor() {
		$key = $_GET['alpine_pdf'] ?? false;
		
		if ( ! $key ) {
			echo 'Coaching Style Quiz PDF Error: Invalid secret key specified.';
			exit;
		}
		
		$entry = array();
		$form = array();
		
		// Store variables in this object
		$this->entry = $entry;
		$this->form = $form;
		$this->pdf = $this->create_pdf();
		
		// Calculate results
		$this->chart_src = '';
		$this->prefix = '';
		
		// Get settings based on results
		$this->result_title = 'Some text for the section "result_title".';
		$this->result_intro = 'Some text for the section "result_intro".';
		$this->result_next_steps = 'Some text for the section "result_next_steps".';
		$this->result_detailed_results = 'Some text for the section "result_detailed_results".';
		
		// Get entry fields
		$this->first_name = 'Radley';
		$this->last_name = 'Sustaire';
		$this->email = 'radley@alchemyandaim.com';
		
		// Get pdf custom fields
		$this->title = $this->get_pdf_setting( 'title' );
		$this->subtitle = $this->get_pdf_setting( 'subtitle' );
		$this->copyright = $this->get_pdf_setting( 'copyright' );
		$this->feedback = $this->get_pdf_setting( 'feedback' );
		$this->next_step = $this->get_pdf_setting( 'next_step' );
		
		// Copyright can use [year]
		$this->copyright = str_replace( '[year]', date('Y'), $this->copyright );
		
		// mPDF settings
		$this->document_title = wp_strip_all_tags( $this->title );
		$this->filename = $this->get_pdf_filename( $this->title );
		
		// Disable image srcset which breaks mpdf
		add_filter( 'max_srcset_image_width', '__return_false' );
		add_filter( 'wp_calculate_image_srcset', '__return_false' );
		add_filter( 'intermediate_image_sizes_advanced', '__return_false' );
		
		// Set document title
		$this->pdf->SetTitle( $this->document_title );
		
		// Add CSS from pdf.css
		$this->add_stylesheet();
		
		// Page: Intro
		$this->add_intro_page();
		
		// Page: Chart
		$this->add_chart_page();
		
		// Page: Feedback
		$this->add_feedback_page();
		
		// Page: Next Step
		$this->add_next_step_page();
		
		// Page: Font test page
		// $this->add_test_fonts_page();
		
		// Finish
		$this->send_pdf();
		
		exit;
	}
	
	public function get_pdf_filename( $entry ) {
		
		$filename = wp_strip_all_tags( $entry );
		$filename = preg_replace('/[^a-zA-Z0-9\-\_ ]+/', '', $filename);
		$filename .= '.pdf';
		
		return $filename;
	}
	
	public function get_pdf_setting( $name ) {
		return "pdf_content_{$name}";
	}
	
	/**
	 * Creates the PDF object with custom settings applied
	 */
	public function create_pdf() {
		require_once( __DIR__ . '/vendor/autoload.php' );
		
		// Use default configs
		/*
		$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
		$fontDirs = $defaultConfig['fontDir'];
		
		$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
		$fontData = $defaultFontConfig['fontdata'];
		*/
		$fontDirs = array();
		$fontData = array();
		
		$path = realpath( __DIR__ . '/../../assets/fonts/' );
		
		if ( $this->use_preview ) {
			return new Class_AH_PDF_Preview();
		}
		
		// Create PDF object
		return new \Mpdf\Mpdf(array(
			
			// Fonts
			'default_font_size' => 14,
			'default_font' => 'lato',
			'fontDir' => array_merge($fontDirs, array($path)),
			'fontdata' => $fontData +
				array(
					'lato' => array(
						'R' => 'lato-regular.ttf',
						'I' => 'lato-italic.ttf',
						'B' => 'lato-bold.ttf',
						'BI' => 'lato-bold-italic.ttf',
					),
					'juana' => array(
						'R' => 'juana-regular.ttf',
						'i' => 'juana-italic.ttf',
					),
					'silversouthscript' => array(
						'R' => 'silver-south-script.ttf',
					),
				),
			
			// Page settings
			'format' => 'LETTER',
			'orientation' => 'L',
			
			// Margins
			'margin_left'   => 0, // 15,
			'margin_right'  => 0, // 15,
			'margin_top'    => 0, // 16,
			'margin_bottom' => 0, // 16,
			'margin_header' => 0, // 9,
			'margin_footer' => 0, // 9,
			
		));
	}
	
	/*
	 * Sends headers and then streams PDF to the browser
	 */
	public function send_pdf() {
		
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
	
	/*
	 * Add stylesheet to the PDF (embedded directly, not a link)
	 */
	public function add_stylesheet() {
		$stylesheet = file_get_contents(__DIR__ . '/../../assets/pdf.css');
		$this->pdf->WriteHTML($stylesheet, 1); // The parameter 1 tells that this is css/style only and no body/html/text
	}
	
	/*
	 * First page of the PDF
	 *
	 * @return void
	 */
	public function add_intro_page() {
		$full_name = trim( $this->first_name . ' ' . $this->last_name );
		
		ob_start();
		?>
<pagebreak page-selector="intro">
	<div class="page page-intro">
		<div class="page-inner">
			<div class="intro-title">
				<h1><?php echo $this->title; ?></h1>
				<h2><?php echo $this->subtitle; ?></h2>
			</div>
			
			<div class="report-for">
				<p>Report for: <?php echo esc_html($full_name); ?></p>
				<p>Date: <?php echo current_time('m/d/Y'); ?></p>
			</div>
		</div>
	</div>
	
	<div class="copyright raised left dark"><?php echo $this->copyright; ?></div>
	
	<div class="logo raised right dark">
		<img src="https://alpinehikerdev.wpengine.com/wp-content/uploads/2022/05/karen-benoy-logo-full-color-rgb.svg" alt="Karen Brody Logo">
	</div>
</pagebreak>
		<?php
		$html = ob_get_clean();
		
		$this->pdf->WriteHTML($html);
	}
	
	/*
	 * 2nd page of the PDF
	 *
	 * @return void
	 */
	public function add_chart_page() {
		ob_start();
		?>
<pagebreak page-selector="chart">
	<div class="page page-chart">
		<div class="page-inner">
		</div>
	</div>
	
	<div class="chart-left">
		<h2><?php echo $this->subtitle; ?></h2>
		<?php echo wpautop($this->result_intro); ?>
	</div>
	
	<div class="chart-right">
		<h2><?php echo $this->result_title; ?></h2>
		<img src="<?php echo esc_attr($this->chart_src); ?>" alt="scores chart svg">
	</div>
	
	<div class="copyright right dark"><?php
		echo $this->document_title . '<br>' . $this->copyright;
	?></div>
	
	<div class="logo chart-logo left light">
		<img src="https://alpinehikerdev.wpengine.com/wp-content/uploads/2022/05/karen-benoy-logo-reverse-rgb.svg" alt="Karen Brody Logo">
	</div>
</pagebreak>
		<?php
		$html = ob_get_clean();
		
		$this->pdf->WriteHTML($html);
	}
	
	/*
	 * 3rd page of the PDF
	 *
	 * @return void
	 */
	public function add_feedback_page() {
		ob_start();
		?>
<pagebreak page-selector="feedback">
	<div class="page page-feedback footer-sloped">
		<div class="page-inner">
			<?php echo wpautop($this->feedback); ?>
		</div>
	</div>
	
	<div class="copyright left light"><?php
		echo $this->document_title . '<br>' . $this->copyright;
	?></div>
	
	<div class="logo right light">
		<img src="https://alpinehikerdev.wpengine.com/wp-content/uploads/2022/05/karen-benoy-logo-reverse-rgb.svg" alt="Karen Brody Logo">
	</div>
</pagebreak>
		<?php
		$html = ob_get_clean();
		
		$this->pdf->WriteHTML($html);
	}
	
	/*
	 * 4th page of the PDF
	 *
	 * @return void
	 */
	public function add_next_step_page() {
		ob_start();
		?>
<pagebreak page-selector="nextstep">
	<div class="photo" style="background-image: url();">
	</div>
	
	<div class="page page-next-step footer-sloped">
		<div class="page-inner">
			<?php echo wpautop($this->next_step); ?>
		</div>
	</div>
	
	<div class="copyright left light"><?php
		echo $this->document_title . '<br>' . $this->copyright;
	?></div>
	
	<div class="logo right light">
		<img src="example.svg" alt="Karen Brody Logo">
	</div>
</pagebreak>
		<?php
		$html = ob_get_clean();
		
		$this->pdf->WriteHTML($html);
	}
	
	/*
	 * Test page with all our custom fonts
	 */
	public function add_test_fonts_page() {
		ob_start();
		?>
	.lato {
		font-family: "lato";
	}
	.juana {
		font-family: "Juana";
	}
	.silversouthscript {
		font-family: "Silversouthscript";
	}
		<?php
		$html = ob_get_clean();
		
		$this->pdf->WriteHtml($html, 1);
		?>
<pagebreak page-selector="test-fonts">
	<h1>Using custom font in the document</h1>
	<p>version 1.0</p>
	
	<p>Example - Default text</p>
	<p style="font-family: Lato">Example - Lato</p>
	<p style="font-family: Lato, sans-serif">Example - Lato, sans-serif</p>
	<p style="font-family: 'Lato'">Example - 'Lato'</p>
	<p class="lato">Example - class="lato"</p>
	
	<p style='font-family: "Lato"'>Example - "Lato" (Double quotes do not work for some reason)</p>
	
	<hr>
	
	<p style="font-family: Lato;">Lato Regular</p>
	<p style="font-family: Lato;"><strong>Lato Bold</strong></p>
	<p style="font-family: Lato;"><em>Lato Italic</em></p>
	<p style="font-family: Lato;"><strong><em>Lato Bold Italic</em></strong></p>
	
	<hr>
	
	<p style="font-family: juana;">Juana Regular</p>
	<p style="font-family: juana;"><em>Juana Italic</em></p>
	<p style="font-family: silversouthscript;">Silversouthscript</p>
</pagebreak>
		<?php
		$html = ob_get_clean();
		
		$this->pdf->WriteHtml($html);
	}
	
}