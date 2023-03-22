<?php

class Class_Hike_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_hike';
	
	public $use_custom_title = false;
	public $use_custom_slug = false;
	public $custom_slug_prefix = false;
	
	public function __construct() {
		
		parent::__construct();
		
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
		$args['labels']['name_admin_bar'] = 'Hikes';
		
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
		
		$args['publicly_queryable'] = false;
		$args['rewrite'] = array(
			'slug' => 'hikes',
			'with_front' => false,
		);
		
		$args['hierarchical'] = true;
		$args['supports'] = array( 'title', 'author' );
		
		$args['taxonomies'] = array();
		
		return $args;
	}
	
}