<?php

class Class_Village_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_village';
	
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
		
		$args['label'] = 'Village';
		
		$args['labels']['name']           = 'Villages';
		$args['labels']['singular_name']  = 'Village';
		$args['labels']['menu_name']      = 'Villages';
		$args['labels']['name_admin_bar'] = 'Villages';
		
		$args['labels']['add_new_item'] = 'Add New Village';
		$args['labels']['all_items'] = 'Villages';
		$args['labels']['add_new'] = 'Add Village';
		$args['labels']['new_item'] = 'New Village';
		$args['labels']['edit_item'] = 'Edit Village';
		$args['labels']['update_item'] = 'Update Village';
		$args['labels']['view_item'] = 'View Village';
		
		// $args['menu_icon'] = 'dashicons-flag';
		// $args['menu_position'] = 22.1;
		$args['show_in_menu'] = 'edit.php?post_type=ah_itinerary';
		
		$args['publicly_queryable'] = false;
		$args['rewrite'] = array(
			'slug' => 'villages',
			'with_front' => false,
		);
		
		$args['hierarchical'] = true;
		$args['supports'] = array( 'title', 'author' );
		
		$args['taxonomies'] = array();
		
		return $args;
	}
	
}