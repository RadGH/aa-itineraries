<?php

class Class_Document_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_document';
	
	public $use_custom_title = false;
	public $use_custom_slug = true;
	public $custom_slug_prefix = false;
	
	public function __construct() {
		
		parent::__construct();
		
		// Only allow access to document if you own the document
		add_action( 'template_redirect', array( $this, 'restrict_document_access' ), 20 );
		
		// Stream the file contents to the visitor's browser
		add_action( 'template_redirect', array( $this, 'stream_document_to_visitor' ), 30 );
		
		// When saving a post, save each user_id as a separate meta key for permission = multiple
		add_action( 'acf/save_post', array( $this, 'save_post_separate_multiple_user_id_meta' ), 40 );
		
	}
	
	/**
	 * Customize the args sent to register_post_type.
	 *
	 * @return array
	 */
	public function get_post_type_args() {
		$args = parent::get_post_type_args();
		
		$args['label'] = 'Document';
		
		$args['labels']['name']           = 'Documents';
		$args['labels']['singular_name']  = 'Document';
		$args['labels']['menu_name']      = 'Client Documents';
		$args['labels']['name_admin_bar'] = 'Documents';
		
		$args['labels']['add_new_item'] = 'Add New Document';
		$args['labels']['all_items'] = 'All Documents';
		$args['labels']['add_new'] = 'Add Document';
		$args['labels']['new_item'] = 'New Document';
		$args['labels']['edit_item'] = 'Edit Document';
		$args['labels']['update_item'] = 'Update Document';
		$args['labels']['view_item'] = 'View Document';
		
		$args['menu_icon'] = 'dashicons-cloud-upload';
		$args['menu_position'] = 22;
		
		$args['publicly_queryable'] = true;
		$args['rewrite'] = array(
			'slug' => 'documents',
			'with_front' => false,
		);
		
		$args['hierarchical'] = false;
		$args['supports'] = array( 'title', 'author' );
		
		$args['taxonomies'] = array( 'ah_document_category' );
		
		return $args;
	}
	
	/**
	 * When registering the post type, also register a taxonomy for the document category
	 * 
	 * @return void
	 */
	public function register_post_type() {
		parent::register_post_type();
		
		$taxonomy = 'ah_document_category';
		
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
				'menu_name'         => __( 'Document Categories' ),
			),
			// Control the slugs used for this taxonomy
			'rewrite' => array(
				'slug'         => 'documents/categories',
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
			// 	echo $this->get_document_status( $post_id );
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
	 * Check if a user has access to a document
	 *
	 * @param $post_id
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function user_has_access( $post_id = null, $user_id = null ) {
		if ( ! $this->is_valid_document( $post_id ) ) return false;
		
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
				$document_user_id = (int) get_field( 'user_id', $post_id );
				if ( $document_user_id == $user_id ) return true;
				break;
				
			case 'multiple':
				// Allow for multiple users
				$document_user_ids = (array) get_field( 'user_ids', $post_id );
				if ( in_array( $user_id, $document_user_ids, true ) ) return true;
				break;
				
		}
		
		// Allow administrators to access all documents
		if ( user_can( $user_id, 'administrator' ) ) return true;
		
		// Not permitted to view this document
		return false;
	}
	
	/**
	 * Prevent unauthorized users from accessing a document
	 *
	 * @return void
	 */
	public function restrict_document_access() {
		
		// Only affect singular document page
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
	 * Stream the file contents to the visitor's browser, or redirect to a URL, depending on document type.
	 *
	 * @return void
	 */
	public function stream_document_to_visitor() {
		// URL must end with /download/ (see rewrites.php for rewrite rule and query var)
		if ( get_query_var( 'ah_action' ) != 'download_document' ) return;
		
		// Allow permissions or give 404 error
		$this->restrict_document_access();
		
		// Get the file
		$post_id = get_the_ID();
		
		$type = get_field( 'type', $post_id );
		
		// Redirect to URL
		if ( $type == 'url' ) {
			$url = get_field( 'url', $post_id );
			
			if ( $url ) {
				wp_redirect( $url );
			}else{
				aa_die('Error: Invalid URL provided. Please contact us for assistance.', array(compact('post_id', 'type', 'url')) );
			}
			exit;
		}
		
		// Stream a file to browser
		if ( $type == 'file' ) {
			$attachment_id = (int) get_field( 'file', $post_id, false );
			
			if ( $attachment_id ) {
				ah_stream_file_to_browser( $attachment_id );
			}else{
				aa_die('Error: File was not uploaded. Please contact us for assistance.', array(compact('post_id', 'type', 'attachment_id')) );
			}
			exit;
		}
		
		aa_die( 'Error: Invalid "type" provided for file. Please contact us for assistance.', array(compact('post_id', 'type')));
		exit;
	}
	
	/**
	 * When saving a post, save each user_id as a separate meta key for permission = multiple
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	public function save_post_separate_multiple_user_id_meta( $post_id ) {
		if ( ! $this->is_valid_document( $post_id ) ) return;
		
		$user_ids = (array) get_field( 'user_ids', $post_id );
		
		delete_post_meta( $post_id, 'ah_multiple_user_id' );
		
		if ( $user_ids ) foreach( $user_ids as $user_id ) {
			add_post_meta( $post_id, 'ah_multiple_user_id', $user_id );
		}
	}
	
	/**
	 * Get a WP_Query containing all of the user's documents
	 *
	 * @param $user_id
	 *
	 * @return false|WP_Query
	 */
	public function get_user_documents( $user_id = null ) {
		if ( $user_id === null ) $user_id = get_current_user_id();
		if ( ! $user_id ) return false;
		
		$post_ids = array();
		
		// Query 1/4: Get documents assigned to all users
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
		
		// Query 2/4: Get documents assigned to a single user
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
		
		// Query 3/4: Get documents assigned to multiple users
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
	
	/**
	 * Check if an document is valid (exists, correct post type)
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	public function is_valid_document( $post_id ) {
		return get_post_type( $post_id ) == $this->get_post_type();
	}
	
}