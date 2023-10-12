<?php

class Class_Itinerary_Template_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_itinerary_tpl'; // max 20 letters, darn
	
	public $use_custom_title = false;
	public $use_custom_slug = false;
	public $custom_slug_prefix = false;
	
	public function __construct() {
		
		parent::__construct();
		
		// Custom page template
		add_filter( 'single_template', array( $this, 'replace_page_template' ) );
		
		// Add a custom meta box with a button to create a new itinerary from the template
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 8 );
		
	}
	
	/**
	 * Checks if the visitor can access this item. Return false if the user does not have access.
	 *
	 * @return bool
	 */
	public function check_page_protection() {
		// Itinerary templates can only be viewed by administrators
		return current_user_can( 'administrator');
	}
	
	public function replace_page_template( $template ) {
		global $post;
		
		if ( $post->post_type == $this->get_post_type() ) {
			$template = AH_PATH . '/templates/single-itinerary-template.php';
		}
		
		return $template;
	}
	
	/**
	 * Customize the args sent to register_post_type.
	 *
	 * @return array
	 */
	public function get_post_type_args() {
		$args = parent::get_post_type_args();
		
		$args['label'] = 'Itinerary Template';
		
		$args['labels']['name']           = 'Itinerary Templates';
		$args['labels']['singular_name']  = 'Itinerary Template';
		$args['labels']['menu_name']      = 'Itinerary Templates';
		$args['labels']['name_admin_bar'] = 'Itinerary Template';
		
		$args['labels']['add_new_item'] = 'Add New Itinerary Template';
		$args['labels']['all_items'] = 'Itinerary Templates';
		$args['labels']['add_new'] = 'Add Itinerary Template';
		$args['labels']['new_item'] = 'New Itinerary Template';
		$args['labels']['edit_item'] = 'Edit Itinerary Template';
		$args['labels']['update_item'] = 'Update Itinerary Template';
		$args['labels']['view_item'] = 'View Itinerary Template';
		
		// $args['menu_icon'] = 'dashicons-location-alt';
		// $args['menu_position'] = 22;
		$args['show_in_menu'] = 'edit.php?post_type=ah_itinerary';
		
		$args['publicly_queryable'] = true;
		$args['rewrite'] = array(
			'slug' => 'itinerary-template',
			'with_front' => false,
		);
		
		$args['hierarchical'] = false;
		$args['supports'] = array( 'title', 'author' );
		
		$args['taxonomies'] = array();
		
		return $args;
	}
	
	/**
	 * Enable or disable "save_post" hooks to allow updating posts without infinite loop
	 *
	 * @param $enabled
	 *
	 * @return void
	 */
	public function toggle_save_post_hooks( $enabled ) {
		parent::toggle_save_post_hooks( $enabled );
		
		if ( $enabled ) {
			// Convert to regular itinerary
			// add_action( 'acf/save_post', array( $this, 'save_post_convert_to_itinerary' ), 50 );
		}else{
			// remove_action( 'acf/save_post', array( $this, 'save_post_convert_to_itinerary' ), 50 );
		}
	}
	
	/**
	 * Convert to a regular itinerary when "Convert to Itinerary" is checked.
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	/*
	public function save_post_convert_to_itinerary( $post_id ) {
		if ( ! $this->is_valid( $post_id ) ) return;
		
		$convert = get_field( 'convert_to_itinerary', $post_id );
		
		if ( $convert ) {
			update_field( 'convert_to_itinerary', false, $post_id );
			
			$args = array(
				'ID' => $post_id,
				'post_type' => AH_Itinerary()->get_post_type(),
			);
			
			$this->toggle_save_post_hooks(false);
			wp_update_post( $args );
			$this->toggle_save_post_hooks(true);
			
			AH_Admin()->add_notice( 'success', 'This itinerary template has been converted to a regular itinerary.', null, 'itinerary-convert', true );
		}
	}
	*/
	
	/**
	 * Add a custom meta box with a button to create a new itinerary from the template
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'itinerary-template',
			'Itinerary Template',
			array( $this, 'render_meta_box' ),
			$this->get_post_type(),
			'side',
			'default'
		);
	}
	
	public function render_meta_box() {
		global $post;
		
		$itinerary_id = get_field( 'itinerary_id', $post->ID );
		
		if ( $itinerary_id ) {
			$itinerary = get_post( $itinerary_id );
			
			if ( $itinerary ) {
				$itinerary_url = get_permalink( $itinerary );
				
				echo '<p><a href="' . $itinerary_url . '" target="_blank" class="button button-primary">View Itinerary</a></p>';
			}
		}
		
		//template_id
		
		$url = admin_url( 'post-new.php' );
		
		$url = add_query_arg(array(
			'post_type' => AH_Itinerary()->get_post_type(),
			'ah_action' => 'load_itinerary_template',
			'template_id' => get_the_ID(),
			'itinerary_id' => 'new',
		), $url);
		
		echo '<p><a href="' . esc_attr($url) . '" class="button">Create itinerary from template</a></p>';
	}
	
}