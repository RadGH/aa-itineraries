<?php

class Class_AH_PDF_Preview {
	
	public $content = '';
	public $extra_css = '';
	
	public function construct() {
	}
	
	public function SetTitle( $title ) {
	}
	
	public function WriteHTML( $string, $ignore_dom = 0 ) {
		if ( $ignore_dom === 0 ) {
			$this->content .= "\n\n" . $string;
		}else{
			$this->extra_css .= "\n" . $string;
		}
	}
	
	public function Output() {
		$stylesheet = file_get_contents(__DIR__ . '/../../assets/pdf-preview.css');
		$this->extra_css .= $stylesheet;
		
		$js = realpath(__DIR__ . '/../../assets/pdf.js');
		$js = str_replace( ABSPATH, '/', $js );
		
		echo '<div class="previewpdf">';
		echo $this->content;
		echo '</div>';
		
		echo "\n\n";
		echo '<style type="text/css">';
		echo $this->extra_css;
		echo '</style>';
		
		// Load jquery and custom JS file
		echo "\n\n";
		echo '<script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>';
		echo "\n\n";
		echo '<script src="'. esc_attr($js) .'"></script>';
		exit;
	}
	
}