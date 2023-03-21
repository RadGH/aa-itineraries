<?php

class Class_Itinerary_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_itinerary';
	
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
		
		$args['label'] = 'Itinerary';
		
		$args['labels']['name']           = 'Itineraries';
		$args['labels']['singular_name']  = 'Itinerary';
		$args['labels']['menu_name']      = 'Itineraries';
		$args['labels']['name_admin_bar'] = 'Itineraries';
		
		$args['labels']['add_new_item'] = 'Add New Itinerary';
		$args['labels']['all_items'] = 'All Itineraries';
		$args['labels']['add_new'] = 'Add Itinerary';
		$args['labels']['new_item'] = 'New Itinerary';
		$args['labels']['edit_item'] = 'Edit Itinerary';
		$args['labels']['update_item'] = 'Update Itinerary';
		$args['labels']['view_item'] = 'View Itinerary';
		
		$args['menu_icon'] = 'dashicons-location-alt';
		$args['menu_position'] = 22;
		
		$args['publicly_queryable'] = true;
		$args['rewrite'] = array(
			'slug' => 'itineraries',
			'with_front' => false,
		);
		
		$args['hierarchical'] = false;
		$args['supports'] = array( 'title', 'author' );
		
		$args['taxonomies'] = array();
		
		return $args;
	}
	
	/**
	 * Remove author metabox, because we use a "User" field instead
	 *
	 * @return void
	 */
	public function remove_unwanted_meta_boxes() {
		parent::remove_unwanted_meta_boxes();
	}
	
}