<?php

class Class_Village_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_village';
	
	public $use_custom_title = false;
	public $use_custom_slug = false;
	public $custom_slug_prefix = false;
	
	public function __construct() {
		
		parent::__construct();
		
		// Custom page template
		add_filter( 'single_template', array( $this, 'replace_page_template' ) );
		
		// Adds links to the Smartsheet meta box to view the spreadsheet or run the sync
		// The field key is for "Smartsheet Actions" in the group "Smartsheet Settings - Village"
		add_filter( 'acf/load_field/key=field_648bb42df03cc', array( $this, 'acf_add_smartsheet_actions' ) );
		
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
			$template = AH_PATH . '/templates/single-village.php';
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
		
		$args['publicly_queryable'] = true;
		$args['rewrite'] = array(
			'slug' => 'village',
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
			array('ah_sync' => 'Smartsheet'),
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
			
			case 'ah_sync':
				$sync_url = AH_Smartsheet_Sync_Hotels_And_Villages()->get_sync_village_or_hotel_link( 'village', $post_id );
				
				if ( $sync_url ) {
					echo sprintf(
						'<a href="%s" class="button button-small button-secondary">Sync</a>',
						esc_attr($sync_url)
					);
				}else{
					echo '&ndash;';
				}
				
				break;
				
			case 'ah_review':
				
				$has_smartsheet = (
					get_post_meta( $post_id, 'smartsheet_id', true )
					&& get_post_meta( $post_id, 'smartsheet_name', true )
				);
				
				$this->display_content_review_column(array(
					'Smartsheet Settings' => $has_smartsheet,
					'Village Name' => get_post_meta( $post_id, 'village_name', true ),
					'Introduction' => get_post_meta( $post_id, 'village_intro', true ),
					'In and Around' => get_post_meta( $post_id, 'around_the_village', true ),
				));
				
				break;
				
		}
	}
	
	public function get_village_name( $post_id ) {
		if ( ! $post_id ) return null;
		$name = get_field( 'village_name', $post_id );
		if ( ! $name ) $name = get_the_title( $post_id );
		return $name;
	}
	
	/*
	 * URLs used on the Smartsheet Actions field group
	 */
	public function get_sync_admin_page_url() {
		return admin_url('admin.php?page=ah-smartsheet-villages-and-hotels');
	}
	
	public function get_smartsheet_sheet_url() {
		return AH_Smartsheet_Sync_Hotels_And_Villages()->get_smartsheet_permalink();
	}
	
	public function get_sync_item_url() {
		return AH_Smartsheet_Sync_Hotels_And_Villages()->get_sync_village_or_hotel_link( 'village', get_the_ID() );
	}
	// END urls
	
	public function get_from_smartsheet( $smartsheet_name, $region ) {
		$args = array(
			'post_type' => $this->get_post_type(),
			'fields' => 'ids',
			'posts_per_page' => 1,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'smartsheet_id' => $smartsheet_name
				),
				array(
					'relation' => 'AND',
					array(
						'smartsheet_name' => $smartsheet_name
					),
					array(
						'smartsheet_region' => $region
					),
				),
			),
		);
		
		$q = new WP_Query($args);
		
		if ( $q->have_posts() ) {
			return (int) $q->posts[0];
		}
		
		return false;
	}
	
}