<?php

class Class_Itinerary_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_itinerary';
	
	public $use_custom_title = false;
	public $use_custom_slug = false;
	public $custom_slug_prefix = false;
	
	public function __construct() {
		
		parent::__construct();
		
		// Only allow access to itinerary if you own the itinerary
		add_action( 'template_redirect', array( $this, 'restrict_itinerary_access' ), 20 );
		
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
		$args['labels']['menu_name']      = 'Client Itineraries';
		$args['labels']['name_admin_bar'] = 'Itineraries';
		
		$args['labels']['add_new_item'] = 'Add New Itinerary';
		$args['labels']['all_items'] = 'All Itineraries';
		$args['labels']['add_new'] = 'Add Itinerary';
		$args['labels']['new_item'] = 'New Itinerary';
		$args['labels']['edit_item'] = 'Edit Itinerary';
		$args['labels']['update_item'] = 'Update Itinerary';
		$args['labels']['view_item'] = 'View Itinerary';
		
		$args['menu_icon'] = 'dashicons-cloud-upload';
		$args['menu_position'] = 22;
		
		$args['publicly_queryable'] = true;
		$args['rewrite'] = array(
			'slug' => 'itineraries',
			'with_front' => false,
		);
		
		$args['hierarchical'] = false;
		$args['supports'] = array( 'title', 'author' );
		
		$args['taxonomies'] = array( 'ah_itinerary_category' );
		
		return $args;
	}
	
	/**
	 * When registering the post type, also register a taxonomy for the itinerary category
	 * 
	 * @return void
	 */
	public function register_post_type() {
		parent::register_post_type();
		
		$taxonomy = 'ah_itinerary_category';
		
		$args = array(
			'hierarchical' => true,
			'labels'       => array(
				'name'              => _x( 'Categories', 'categories' ),
				'singular_name'     => _x( 'Category', 'category' ),
				'search_items'      => __( 'Search Categories' ),
				'all_items'         => __( 'All Categories' ),
				'parent_item'       => __( 'Parent Category' ),
				'parent_item_colon' => __( 'Parent Category:' ),
				'edit_item'         => __( 'Edit Categories' ),
				'update_item'       => __( 'Update Categories' ),
				'add_new_item'      => __( 'Add New Categories' ),
				'new_item_name'     => __( 'New Category Name' ),
				'menu_name'         => __( 'Itinerary Categories' ),
			),
			// Control the slugs used for this taxonomy
			'rewrite' => array(
				'slug'         => 'itineraries/categories',
				'with_front'   => true,
				'hierarchical' => true,
			),
		);
		
		register_taxonomy( $taxonomy, $this->get_post_type(), $args );
	}
	
	
	/**
	 * Used to add or remove columns to the dashboard list view
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function customize_columns( $columns ) {
		return array_merge(
			array_slice( $columns, 0, 2),
			// array('ah_status' => 'Status'),
			array_slice( $columns, 2, null),
		);
	}
	
	
	/**
	 * Used to display column content in customized columns
	 *
	 * @param string $column
	 * @param int $post_id
	 *
	 * @return void
	 */
	public function display_columns( $column, $post_id ) {
		switch( $column ) {
			// case 'ah_status':
			// 	echo $this->get_itinerary_status( $post_id );
			// 	break;
		}
	}
	
	/**
	 * Remove author metabox, because we use a "User" field instead
	 *
	 * @return void
	 */
	public function remove_unwanted_meta_boxes() {
		parent::remove_unwanted_meta_boxes();
		
		// Remove author metabox
		remove_meta_box( 'authordiv', $this->get_post_type(), 'normal' );
		remove_meta_box( 'authordiv', $this->get_post_type(), 'side' );
	}
	
	/**
	 * Check if an itinerary is valid (exists, correct post type)
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	public function is_valid_itinerary( $post_id ) {
		return get_post_type( $post_id ) == $this->get_post_type();
	}
	
	/**
	 * Check if a user has access to a itinerary
	 *
	 * @param $post_id
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function user_has_access( $post_id = null, $user_id = null ) {
		if ( ! $this->is_valid_itinerary( $post_id ) ) return false;
		
		// Must be logged in, even if permission is set to "All Users"
		if ( $user_id === null ) $user_id = get_current_user_id();
		if ( ! $user_id ) return false;
		
		$permission = get_field( 'permission', $post_id );
		
		switch( $permission ) {
			
			case 'all':
				// Allow for all users (note: must still be signed in)
				return true;
				
			case 'single':
				// Allow for a single user
				$itinerary_user_id = (int) get_field( 'user_id', $post_id );
				if ( $itinerary_user_id == $user_id ) return true;
				break;
				
			case 'multiple':
				// Allow for multiple users
				$itinerary_user_ids = (array) get_field( 'user_ids', $post_id );
				if ( in_array( $user_id, $itinerary_user_ids, true ) ) return true;
				break;
				
		}
		
		// Allow administrators to access all itineraries
		if ( user_can( $user_id, 'administrator' ) ) return true;
		
		// Not permitted to view this itinerary
		return false;
	}
	
	/**
	 * Prevent unauthorized users from accessing a itinerary
	 *
	 * @return void
	 */
	public function restrict_itinerary_access() {
		
		// Only affect singular itinerary page
		if ( ! is_singular( $this->get_post_type() ) ) return;
		
		// Allow users who have access
		if ( $this->user_has_access( get_the_ID() ) ) return;
		
		// If not logged in, go to login page
		if ( ! is_user_logged_in() ) {
			$url = site_url('/account/not-logged-in/');
			$url = add_query_arg(array('redirect_to' => urlencode($_SERVER['REQUEST_URI'])), $url);
			wp_redirect($url);
			exit;
		}
		
		// Block any other access
		get_template_part( '404' );
		exit;
		
	}
	
	/**
	 * Get a WP_Query containing all of the user's itineraries
	 *
	 * @param $user_id
	 *
	 * @return false|WP_Query
	 */
	public function get_user_itineraries( $user_id = null ) {
		if ( $user_id === null ) $user_id = get_current_user_id();
		if ( ! $user_id ) return false;
		
		$post_ids = array();
		
		// Query 1/4: Get itineraries assigned to all users
		$args = array(
			'post_type' => $this->get_post_type(),
			'nopaging' => true,
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => 'permission',
					'value' => 'all',
				),
			),
		);
		
		$q = new WP_Query( $args );
		
		if ( $q->have_posts() ) $post_ids = array_merge( $post_ids, $q->posts );
		
		// Query 2/4: Get itineraries assigned to a single user
		$args = array(
			'post_type' => $this->get_post_type(),
			'nopaging' => true,
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => 'permission',
					'value' => 'single',
				),
				array(
					'key' => 'user_id',
					'value' => $user_id,
				),
			),
		);
		
		$q = new WP_Query( $args );
		
		if ( $q->have_posts() ) $post_ids = array_merge( $post_ids, $q->posts );
		
		// Query 3/4: Get itineraries assigned to multiple users
		$args = array(
			'post_type' => $this->get_post_type(),
			'nopaging' => true,
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => 'permission',
					'value' => 'multiple',
				),
				array(
					'key' => 'ah_multiple_user_id',
					'value' => $user_id,
				),
			),
		);
		
		$q = new WP_Query( $args );
		
		if ( $q->have_posts() ) $post_ids = array_merge( $post_ids, $q->posts );
		
		// Query 4/4: Combine all those posts into one new query
		$args = array(
			'post_type' => $this->get_post_type(),
			'nopaging' => true,
			'post__in' => $post_ids,
		);
		
		return new WP_Query($args);
	}
	
}