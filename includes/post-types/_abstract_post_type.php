<?php

abstract class Class_Abstract_Post_Type {
	
	// Settings to override in child classes
	public $post_type = null;
	public $use_custom_title = false;
	public $use_custom_slug = false;
	public $custom_slug_prefix = null; // post type if null
	
	// Do not override this directly, instead use $this->get_post_type_args()
	public $default_args = array(
		'label'                 => 'Item',
		'labels'                => array(
			'name'                  => 'Items',
			'singular_name'         => 'Item',
			'menu_name'             => 'Items',
			'name_admin_bar'        => 'Items',
			'archives'              => 'Item Archives',
			'attributes'            => 'Item Attributes',
			'parent_item_colon'     => 'Parent Item:',
			'all_items'             => 'All Items',
			'add_new_item'          => 'Add New Item',
			'add_new'               => 'Add New',
			'new_item'              => 'New Item',
			'edit_item'             => 'Edit Item',
			'update_item'           => 'Update Item',
			'view_item'             => 'View Item',
			'view_items'            => 'View Items',
			'search_items'          => 'Search Item',
			'not_found'             => 'Not found',
			'not_found_in_trash'    => 'Not found in Trash',
			'featured_image'        => 'Featured Image',
			'set_featured_image'    => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image'    => 'Use as featured image',
			'insert_into_item'      => /* @lang text */ 'Add into item',
			'uploaded_to_this_item' => 'Uploaded to this item',
			'items_list'            => 'Items list',
			'items_list_navigation' => 'Items list navigation',
			'filter_items_list'     => 'Filter items list',
		),
		'supports'              => array( 'title', 'author', 'revisions' ),
		'hierarchical'          => true,
		'public'                => true,
		'show_ui'               => true,
		
		// 'show_in_menu'          => false,
		'show_in_menu'          => true,
		
		//'menu_position'         => false,
		'menu_position'         => 0,
		
		'menu_icon'             => 'dashicons-id-alt',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,
		'capability_type'       => 'page',
		'rewrite'               => false,
		'has_archive'           => false,
	);
	
	
	public function __construct() {
		
		add_action( 'init', array( $this, 'register_post_type' ), 5 );
		
		add_filter( "manage_edit-{$this->post_type}_columns", array( $this, 'customize_columns' ), 50 );
		
		add_action( "manage_{$this->post_type}_posts_custom_column", array( $this, 'display_columns' ), 50, 2 );
		
		// Hooks when saving posts, to be un-hooked in case of need to update post mid-save
		$this->toggle_save_post_hooks( true );
		
		// Remove unwanted meta boxes from edit screen
		add_action( 'add_meta_boxes', array( $this, 'remove_unwanted_meta_boxes' ), 30 );
		
		// Remove unwanted columns from list screen
		add_filter( "manage_edit-{$this->post_type}_columns", array( $this, 'remove_unwanted_post_columns' ), 30 );
	}
	
	/**
	 * Get the full name of the owner of this post.
	 *
	 * @param $post_id
	 *
	 * @return false|string
	 */
	public function get_owner_full_name( $post_id ) {
		$user_id = $this->get_owner( $post_id );
		if ( ! $user_id ) return false;
		
		return ah_get_user_full_name( $user_id );
	}
	
	/**
	 * Get the User ID who owns the post. By default, this returns the post author.
	 *
	 * @param $post_id
	 *
	 * @return array|false|int|string
	 */
	public function get_owner( $post_id ) {
		if ( ! $this->is_valid( $post_id ) ) return false;
		
		return get_post_field( 'post_author', $post_id ) ?: false;
	}
	
	/**
	 * Changes the owner of a post. By default, this changes the post author.
	 *
	 * @param $post_id
	 * @param $user_id
	 *
	 * @return void
	 */
	public function set_owner( $post_id, $user_id ) {
		if ( ! $this->is_valid( $post_id ) ) return;
		
		$args = array(
			'ID' => $post_id,
			'post_author' => $user_id ?: false,
		);
		
		$this->toggle_save_post_hooks(false);
		wp_update_post( $args );
		$this->toggle_save_post_hooks(true);
	}
	
	
	/**
	 * Enable or disable "save_post" hooks to allow updating posts without infinite loop
	 *
	 * @param $enabled
	 *
	 * @return void
	 */
	public function toggle_save_post_hooks( $enabled ) {
		if ( $enabled ) {
			// After post updated and metadata saved, set custom title
			if ( $this->use_custom_title ) add_action( 'acf/save_post', array( $this, 'set_custom_post_title' ), 40 );
			if ( $this->use_custom_slug ) add_action( 'wp_insert_post', array( $this, 'set_custom_post_slug' ), 10, 3 );
		}else{
			if ( $this->use_custom_title ) remove_action( 'acf/save_post', array( $this, 'set_custom_post_title' ) );
			if ( $this->use_custom_slug ) remove_action( 'wp_insert_post', array( $this, 'set_custom_post_slug' ), 10 );
		}
	}
	
	
	/**
	 * After post updated: Set custom slug (post_name)
	 *
	 * @param $post_id
	 * @param $post
	 * @param $updated
	 *
	 * @return void
	 */
	public function set_custom_post_slug( $post_id, $post = null, $updated = false ) {
		if ( ! $post instanceof WP_Post ) $post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) return;
		if ( $post->post_type != $this->get_post_type() ) return;
		
		// check current slug
		$post_slug = $post->post_name;
		if ( !empty($post_slug) ) return;
		
		// no slug yet, create one
		$slug = $this->get_custom_post_slug( $post_id );
		
		// save new slug as post_name
		$args = array(
			'ID' => $post_id,
			'post_name' => $slug,
		);
		
		// unhook and rehook to avoid infinite loop
		$this->toggle_save_post_hooks( false );
		wp_update_post($args);
		$this->toggle_save_post_hooks( true );
	}
	
	
	/**
	 * After post updated: Set custom title
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	public function set_custom_post_title( $post_id ) {
		$post = get_post($post_id);
		if ( !$post instanceof WP_Post ) return;
		
		// Ignore deletion
		if ( $post->post_status == 'trash' ) return;
		
		// Verify post type
		if ( $post->post_type != $this->get_post_type() ) return;
		
		// Get title (intended to be customized by each individual subclass)
		$title = $this->get_custom_post_title( $post_id );
		
		// If the title is different, update it
		if ( $post->post_title !== $title ) {
			$args = array(
				'ID' => $post_id,
				'post_title' => $title,
			);
			
			// Unhook and re-hook to avoid infinite loop
			$this->toggle_save_post_hooks(false);
			wp_update_post($args);
			$this->toggle_save_post_hooks(true);
		}
	}
	
	/**
	 * Generates a random slug based on this post type
	 *
	 * @param int|null $post_id
	 *
	 * @return string
	 */
	public function get_custom_post_slug( $post_id = null ) {
		$prefix = $this->custom_slug_prefix;
		if ( $prefix === null ) $prefix = $this->get_post_type() . '-';
		if ( $prefix === false ) $prefix = '';
		
		return uniqid( $prefix );
	}
	
	
	/**
	 * Generate a custom post title from custom fields
	 *
	 * @param $post_id
	 *
	 * @return string
	 */
	public function get_custom_post_title( $post_id ) {
		return $this->get_post_type_args()['label'] . ' #'. $post_id;
	}
	
	
	/**
	 * Get post type
	 *
	 * @return null
	 */
	public function get_post_type() {
		return $this->post_type;
	}
	
	
	/**
	 * Return args used to register the post type.
	 * Custom post types extending this class should overwrite this and add any custom args on top of the default ones.
	 *
	 * @return array
	 */
	function get_post_type_args() {
		return $this->default_args;
	}
	
	
	/**
	 * Register the post type.
	 * Custom post types should override get_post_type_args to customize any args.
	 * Applies to:
	 *  1. Assessments
	 *  2. Teams
	 *  3. Entries
	 *  4. Sessions
	 *
	 * @return void
	 */
	function register_post_type() {
		$post_type = $this->get_post_type();
		$args = $this->get_post_type_args();
		
		register_post_type( $post_type, $args );
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
			// 	echo 'Something';
			// 	break;
		}
	}
	
	
	/**
	 * Remove unwanted meta boxes from edit screen
	 *
	 * @return void
	 */
	public function remove_unwanted_meta_boxes() {
		remove_meta_box('wpseo_meta', $this->get_post_type(), 'normal');
		remove_meta_box('edit-box-ppr', $this->get_post_type(), 'normal');
		remove_meta_box('evoia_mb', $this->get_post_type(), 'normal');
		
		// Move author div to side
		remove_meta_box( 'authordiv', $this->get_post_type(), 'normal' );
		add_meta_box( 'authordiv', 'Author', 'post_author_meta_box', $this->get_post_type(), 'side' );
	}
	
	
	/**
	 * Remove unwanted columns from list screen
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function remove_unwanted_post_columns( $columns ) {
		unset( $columns['new_post_thumb'] );
		unset( $columns['wpseo-score'] );
		unset( $columns['wpseo-title'] );
		unset( $columns['wpseo-metadesc'] );
		unset( $columns['wpseo-focuskw'] );
		unset( $columns['wpseo-links'] );
		unset( $columns['wpseo-linked'] );
		
		// Move to end of list
		$date = $columns['date'];
		unset( $columns['date'] );
		$columns['date'] = $date;
		
		return $columns;
	}
	
	/**
	 * Check if the given post ID is valid (exists and the correct post type)
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	public function is_valid( $post_id ) {
		return get_post_type( $post_id ) == $this->get_post_type();
	}
	
}