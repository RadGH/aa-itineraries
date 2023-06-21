<?php

class Class_Hike_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_hike';
	
	public $use_custom_title = false;
	public $use_custom_slug = false;
	public $custom_slug_prefix = false;
	
	public function __construct() {
		
		parent::__construct();
		
		add_filter( 'single_template', array( $this, 'replace_page_template' ) );
		
		// Adds links to the Smartsheet meta box to view the spreadsheet or run the sync
		// The field key is for "Smartsheet Actions" in the group "Smartsheet Settings - Hike"
		add_filter( 'acf/load_field/key=field_648bb503354c6', array( $this, 'acf_add_smartsheet_actions' ) );
		
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
	
	/**
	 * Add a column that shows any information that is missing
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function customize_columns( $columns ) {
		return array_merge(
			array_slice( $columns, 0, 2),
			array('ah_review' => 'Content Review'),
			array_slice( $columns, 2, null),
		);
	}
	
	/**
	 * Display custom columns html
	 *
	 * @param string $column
	 * @param int $post_id
	 *
	 * @return void
	 */
	public function display_columns( $column, $post_id ) {
		switch( $column ) {
			
			case 'ah_review':
				
				$this->display_content_review_column(array(
					'Smartsheet ID' => get_post_meta( $post_id, 'smartsheet_id', true ),
					'Hike Name' => get_post_meta( $post_id, 'hike_name', true ),
					'Summary' => get_post_meta( $post_id, 'summary', true ),
					'Content' => get_post_meta( $post_id, 'content', true ),
					'Elevation Diagram' => get_post_meta( $post_id, 'elevation_diagram', true ),
					'Topographic Map' => get_post_meta( $post_id, 'topographic_map', true ),
					'Links' => get_post_meta( $post_id, 'link_links_0_url', true ),
				));
				
				break;
			
		}
	}
	
	public function get_hike_name( $post_id ) {
		if ( ! $post_id ) return null;
		$name = get_field( 'hike_name', $post_id );
		if ( ! $name ) $name = get_the_title( $post_id );
		return $name;
	}
	
	/*
	 * URLs used on the Smartsheet Actions field group
	 */
	public function get_sync_admin_page_url() {
		return admin_url('admin.php?page=ah-smartsheet-hikes');
	}
	
	public function get_smartsheet_sheet_url() {
		return AH_Smartsheet_Sync_Hikes()->get_smartsheet_permalink();
	}
	
	public function get_sync_item_url() {
		return AH_Smartsheet_Sync_Hikes()->get_sync_hike_link( get_the_ID() );
	}
	
	/**
	 * Search for a hike with the given name and region. Returns the hike ID if found, or false if not found.
	 *
	 * @param string       $hike_name  Name of the hike: "Schwarzwaldalp to Grindelwald"
	 * @param string|null  $region     Short name of the region: "BO"
	 *
	 * @return int|false
	 */
	public function get_hike_by_name_and_region( $hike_name, $region = null ) {
		
		// For now, just use the hike name
		return AH_Smartsheet_Sync_Hikes()->get_hike_by_smartsheet_id( $hike_name );
		
		/*
		$query = new WP_Query(array(
			'post_type' => $this->get_post_type(),
			'meta_query' => array(
				array(
					'key' => 'smartsheet_id',
					'value' => $hike_name,
				),
				array(
					'key' => 'smartsheet_region',
					'value' => $region,
				),
			),
		));
		
		if ( $query->have_posts() ) {
			return $query->posts[0]->ID;
		} else {
			return false;
		}
		*/
	}
	
}