<?php

class Class_AH_Editor {
	
	public function __construct() {
		
		// add_filter( 'tiny_mce_before_init', array($this, 'custom_editor_formats'), 20 );
		
	}
	
	/**
	 * Get the post type of the current (admin) screen
	 *
	 * @return false|string
	 */
	public function get_current_post_type() {
		global $typenow, $current_screen;
		
		if ( get_post_type() ) return get_post_type();
		if ( isset($typenow) && $typenow ) return $typenow;
		if ( isset($current_screen) && $current_screen instanceof WP_Screen ) return $current_screen->post_type;
		return false;
	}
	
	/*
	public function custom_editor_formats( $args ) {
		
		$post_type = $this->get_current_post_type();
		$itinerary_post_types = ah_get_itinerary_post_types();
		
		// Only apply to itinerary post types
		if ( ! in_array( $post_type, $itinerary_post_types ) ) {
			return $args;
		}
		
		$style_formats = array(
			array(
				'title'   => 'Colors',
				'items' => array(
					array(
						'title'   => 'Black',
						'inline' => 'span',
						'classes'  => 'color-black',
					),
					array(
						'title'   => 'White',
						'inline' => 'span',
						'classes'  => 'color-white',
					),
					array(
						'title'   => 'Blue',
						'inline' => 'span',
						'classes'  => 'color-blue',
					),
				),
			),
			array(
				'title'   => 'Font Family',
				'items' => array(
					array(
						'title'   => 'Fraunces',
						'inline' => 'span',
						'classes'  => 'font-fraunces',
					),
					array(
						'title'   => 'Montserrat',
						'inline' => 'span',
						'classes'  => 'font-montserrat',
					),
					array(
						'title'   => 'Ammer Handwriting',
						'inline' => 'span',
						'classes'  => 'font-ammer handwriting',
					),
					array(
						'title'   => 'Freight',
						'inline' => 'span',
						'classes'  => 'font-freight',
					),
				),
			),
			array(
				'title'   => 'Font Weight',
				'items' => array(
					array(
						'title'   => 'Light',
						'inline' => 'span',
						'classes'  => 'weight-light', // 300
					),
					array(
						'title'   => 'Regular',
						'inline' => 'span',
						'classes'  => 'weight-regular', // 400
					),
					array(
						'title'   => 'Semi-bold',
						'inline' => 'span',
						'classes'  => 'weight-semibold', // 600
					),
					array(
						'title'   => 'Bold',
						'inline' => 'span',
						'classes'  => 'weight-bold', // 700
					),
				),
			),
			array(
				'title'   => 'Buttons',
				'items' => array(
					array(
						'title' => 'Ghost Button',
						'selector' => 'a',
						'classes' => 'button',
						'exact' => true,
					),
					array(
						'title' => 'Primary Button',
						'selector' => 'a',
						'classes' => 'button button-primary',
						'exact' => true,
					),
					array(
						'title' => 'Secondary Button',
						'selector' => 'a',
						'classes' => 'button button-secondary',
						'exact' => true,
					),
				),
			),
			array(
				'title'   => 'Inline',
				'items' => array(
					array(
						'title'   => 'Underline',
						'icon'    => 'underline',
						'inline' => 'span',
						'classes'  => 'text-underline',
					),
					array(
						'title'   => 'Strikethrough',
						'format'  => 'strikethrough',
						'icon'    => 'strikethrough',
					),
					array(
						'title'   => 'Superscript',
						'format'  => 'superscript',
						'icon'    => 'superscript',
					),
					array(
						'title'   => 'Subscript',
						'format'  => 'subscript',
						'icon'    => 'subscript',
					),
					array(
						'title'   => 'Code',
						'format'  => 'code',
						'icon'    => 'code',
					),
				),
			),
		);
		
		// Insert the array, JSON ENCODED, into 'style_formats'
		$args['style_formats'] = json_encode( $style_formats );
		
		return $args;
	}
	*/
	
}