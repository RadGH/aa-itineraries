<?php

class Class_Hike_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_hike';
	
	public $use_custom_title = false;
	public $use_custom_slug = false;
	public $custom_slug_prefix = false;
	
	public function __construct() {
		
		parent::__construct();
		
		add_filter( 'single_template', array( $this, 'replace_page_template' ) );
		
	}
	
	/**
	 * Checks if the visitor can access this item. Return false if the user does not have access.
	 *
	 * @return bool
	 */
	public function check_page_protection() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) return false;
		
		$owner_id = $this->get_owner( get_the_ID() );
		if ( $owner_id != $user_id ) return false;
		
		return true;
	}
	
	public function replace_page_template( $template ) {
		global $post;
		
		if ( $post->post_type == $this->get_post_type() ) {
			$template = AH_PATH . '/templates/single-hike.php';
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
		
		$args['label'] = 'Hike';
		
		$args['labels']['name']           = 'Hikes';
		$args['labels']['singular_name']  = 'Hike';
		$args['labels']['menu_name']      = 'Hikes';
		$args['labels']['name_admin_bar'] = 'Hike';
		
		$args['labels']['add_new_item'] = 'Add New Hike';
		$args['labels']['all_items'] = 'Hikes';
		$args['labels']['add_new'] = 'Add Hike';
		$args['labels']['new_item'] = 'New Hike';
		$args['labels']['edit_item'] = 'Edit Hike';
		$args['labels']['update_item'] = 'Update Hike';
		$args['labels']['view_item'] = 'View Hike';
		
		// $args['menu_icon'] = 'dashicons-flag';
		// $args['menu_position'] = 22.1;
		$args['show_in_menu'] = 'edit.php?post_type=ah_itinerary';
		
		$args['publicly_queryable'] = true;
		$args['rewrite'] = array(
			'slug' => 'hike',
			'with_front' => false,
		);
		
		$args['hierarchical'] = true;
		$args['supports'] = array( 'title', 'author' );
		
		$args['taxonomies'] = array();
		
		return $args;
	}
	
	public function get_hike_name( $post_id ) {
		if ( ! $post_id ) return null;
		$name = get_field( 'hike_name', $post_id );
		if ( ! $name ) $name = get_the_title( $post_id );
		return $name;
	}
	
}