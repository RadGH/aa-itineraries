<?php

class Class_Hotel_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_hotel';
	
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
		if ( current_user_can( 'administrator' ) ) {
			return true;
		}
		
		$user_id = get_current_user_id();
		if ( ! $user_id ) return false;
		
		$owner_id = $this->get_owner( get_the_ID() );
		if ( $owner_id != $user_id ) return false;
		
		return true;
	}
	
	public function replace_page_template( $template ) {
		global $post;
		
		if ( $post->post_type == $this->get_post_type() ) {
			$template = AH_PATH . '/templates/single-hotel.php';
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
		
		$args['label'] = 'Hotel';
		
		$args['labels']['name']           = 'Hotels';
		$args['labels']['singular_name']  = 'Hotel';
		$args['labels']['menu_name']      = 'Hotels';
		$args['labels']['name_admin_bar'] = 'Hotels';
		
		$args['labels']['add_new_item'] = 'Add New Hotel';
		$args['labels']['all_items'] = 'Hotels';
		$args['labels']['add_new'] = 'Add Hotel';
		$args['labels']['new_item'] = 'New Hotel';
		$args['labels']['edit_item'] = 'Edit Hotel';
		$args['labels']['update_item'] = 'Update Hotel';
		$args['labels']['view_item'] = 'View Hotel';
		
		// $args['menu_icon'] = 'dashicons-flag';
		// $args['menu_position'] = 22.1;
		$args['show_in_menu'] = 'edit.php?post_type=ah_itinerary';
		
		$args['publicly_queryable'] = true;
		$args['rewrite'] = array(
			'slug' => 'hotel',
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
			array('ah_village_map' => 'Village Map'),
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
				$sync_url = AH_Smartsheet_Sync_Hotels_And_Villages()->get_sync_village_or_hotel_link( 'hotel', $post_id );
				
				if ( $sync_url ) {
					echo sprintf(
						'<a href="%s" class="button button-small button-secondary">Sync</a>',
						esc_attr($sync_url)
					);
				}else{
					echo '&ndash;';
				}
				
				break;
				
			case 'ah_village_map':
				$image_id = get_post_meta( $post_id, 'village_map', true );
				if ( $image_id ) {
					ah_display_image( $image_id, 150, 150 );
				}else{
					echo '&ndash;';
				}
				break;
				
			case 'ah_review':
				
				$this->display_content_review_column(array(
					'Village' => get_post_meta( $post_id, 'village', true ),
					'Hotel Name' => get_post_meta( $post_id, 'hotel_name', true ),
					'Phone' => get_post_meta( $post_id, 'phone', true ),
					'Description' => get_post_meta( $post_id, 'description', true ),
				));
				
				break;
				
		}
	}
	
}