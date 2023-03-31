<?php

class Class_Itinerary_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_itinerary';
	
	public $use_custom_title = false;
	public $use_custom_slug = false;
	public $custom_slug_prefix = false;
	
	public function __construct() {
		
		parent::__construct();
		
		// When creating a new itinerary, ask to create from template
		add_action( 'admin_notices', array( $this, 'suggest_template' ) );
		
		// Load itinerary data from template
		add_action( 'admin_init', array( $this, 'load_from_template' ) );
		
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
		
		$post_id = get_the_ID();
		
		$user_ids = $this->get_assigned_users( $post_id );
		if ( ! in_array( $user_id, $user_ids, true ) ) return false;
		
		return true;
	}
	
	/**
	 * Get an array of user ids assigned to this itinerary
	 *
	 * @param $post_id
	 *
	 * @return int[]
	 */
	public function get_assigned_users( $post_id ) {
		$user_ids = get_post_meta( $post_id, 'assigned_user_id' );
		if ( ! is_array($user_ids) ) $user_ids = array();
		
		$user_ids = array_map( 'intval', $user_ids );
		
		return $user_ids;
	}
	
	/**
	 * Get itineraries assigned to a user
	 *
	 * @param int|null $user_id
	 * @param array $custom_args
	 *
	 * @return WP_Query
	 */
	public function get_user_itineraries( $user_id = null, $custom_args = array() ) {
		if ( $user_id === null ) $user_id = get_current_user_id();
		
		$args = array(
			'post_type' => $this->get_post_type(),
			'nopaging' => true,
			'meta_query' => array(
				array(
					'key' => 'assigned_user_id',
					'value' => $user_id,
				),
			),
		);
		
		return new WP_Query( $args );
	}
	
	/**
	 * When adding a new itinerary, ask if it should load a template instead
	 *
	 * @return void
	 */
	public function suggest_template() {
		$screen = function_exists('get_current_screen') ? get_current_screen() : false;
		if ( ! $screen ) return;
		
		if ( $screen->id != $this->get_post_type() ) return;
		if ( $screen->base != 'post' ) return;
		if ( $screen->action != 'add' ) return;
		
		$args = array(
			'post_type' => AH_Itinerary_Template()->get_post_type(),
			'nopaging' => true,
		);
		$templates = new WP_Query($args);
		
		?>
		<div class="notice notice-info">
			<p>You are creating a new itinerary. Would you like to load information from a template? This will overwrite ALL information in this itinerary.</p>
			
			<form action="post-new.php?post_type=<?php echo $this->get_post_type(); ?>" method="POST">
				
				<input type="hidden" name="ah_action" value="load_itinerary_template" >
				<input type="hidden" name="itinerary_id" value="<?php echo get_the_ID(); ?>" >
				
				<p><label for="ah_template_id"></label></p>
				
				<select name="template_id" id="ah_template_id">
					<option value="">&ndash; Select Template &ndash;</option>
					<?php
					if ( $templates->have_posts() ) foreach( $templates->posts as $post ) {
						echo sprintf(
							'<option value="%s">%s</option>',
							esc_attr($post->ID),
							esc_html($post->post_title)
						);
					}
					?>
				</select>
				
				<p class="submit"><input type="submit" class="button button-secondary" value="Load Template"></p>
				
			</form>
		</div>
		<?php
	}
	
	/**
	 * Load itinerary data from template
	 *
	 * @return void
	 */
	public function load_from_template() {
		$action = $_POST['ah_action'] ?? false;
		if ( $action != 'load_itinerary_template' ) return;
		
		$itinerary_id = (int) $_POST['itinerary_id'];
		$template_id = (int) $_POST['template_id'];
		if ( ! $itinerary_id || ! $template_id ) return;
		
		// Check if both are valid
		if (
			! $this->is_valid($itinerary_id) ||
			! AH_Itinerary_Template()->is_valid($template_id)
		) {
			aa_die(__FUNCTION__ . ' invalid template ID', compact('action', 'itinerary_id', 'template_id'));
		}
		
		// Collect each ACF field used on the template
		$fields = array();
		
		// Get each field group used on the itinerary template page
		$field_groups = acf_get_field_groups(array('post_type' => AH_Itinerary_Template()->get_post_type()));
		
		foreach( $field_groups as $group ) {
			// $group = array with ID, key, title ... https://radleysustaire.com/s3/743e23/
			$group_key = $group['key'];
			
			// $sub_fields = array with ID, key, label, type ... https://radleysustaire.com/s3/6aa031/
			$sub_fields = acf_get_fields( $group_key );
			
			foreach( $sub_fields as $f ) $fields[] = $f;
		}
		
		// Get the value from the template for of every one of those fields, then save it to the itinerary
		foreach( $fields as $field ) {
			$value = get_field( $field['name'], $template_id, false );
			
			update_field( $field['name'], $value, $itinerary_id );
		}
		
		// Set the post title
		$args = array(
			'ID' => $itinerary_id,
			'post_title' => 'Copy of ' . get_the_title( $template_id ),
		);
		
		// Publish the itinerary so that it can be edited
		if ( get_post_status( $itinerary_id ) != 'publish' ) {
			$args['post_status'] = 'draft';
		}
		
		$this->toggle_save_post_hooks(false);
		wp_update_post( $args );
		$this->toggle_save_post_hooks(true);
		
		$link = admin_url( 'post.php?post='. $itinerary_id .'&action=edit' );
		
		wp_redirect( $link );
		exit;
	}
	
	public function replace_page_template( $template ) {
		global $post;
		
		if ( $post->post_type == $this->get_post_type() ) {
			$template = AH_PATH . '/templates/single-itinerary.php';
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
		
		$args['label'] = 'Itinerary';
		
		$args['labels']['name']           = 'Itineraries';
		$args['labels']['singular_name']  = 'Itinerary';
		$args['labels']['menu_name']      = 'Itineraries';
		$args['labels']['name_admin_bar'] = 'Itinerary';
		
		$args['labels']['add_new_item'] = 'Add New Itinerary';
		$args['labels']['all_items'] = 'Itineraries';
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
	 * Used to add or remove columns to the dashboard list view
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function customize_columns( $columns ) {
		return array_merge(
			array_slice( $columns, 0, 2),
			array('ah_users' => 'Assigned To'),
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

			case 'ah_users':
				$user_ids = $this->get_assigned_users( $post_id );
				
				if ( $user_ids ) {
					$names = array();
					foreach( $user_ids as $user_id ) {
						$name = ah_get_user_full_name( $user_id );
						$url = get_edit_user_link( $user_id );
						$names[] = sprintf(
							'<a href="%s">%s</a>',
							esc_attr($url),
							esc_html($name)
						);
					}
					echo implode(', ', $names);
				}else{
					echo '<em style="opacity: 0.5;">Not assigned</em>';
				}
				break;
			
		}
	}
	
	/**
	 * Enable or disable "save_post" hooks to allow updating posts without infinite loop
	 *
	 * @param $enabled
	 *
	 * @return void
	 */
	public function toggle_save_post_hooks( $enabled ) {
		parent::toggle_save_post_hooks( $enabled );
		
		if ( $enabled ) {
			// Make the author match the assigned user for the post
			add_action( 'acf/save_post', array( $this, 'save_post_split_user_meta_keys' ), 40 );
			
			// Convert to itinerary template
			add_action( 'acf/save_post', array( $this, 'save_post_convert_to_template' ), 50 );
		}else{
			remove_action( 'acf/save_post', array( $this, 'save_post_split_user_meta_keys' ), 40 );
			remove_action( 'acf/save_post', array( $this, 'save_post_convert_to_template' ), 50 );
		}
	}
	
	/**
	 * Convert to itinerary template when "Convert to Template" is checked.
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	public function save_post_convert_to_template( $post_id ) {
		if ( ! $this->is_valid( $post_id ) ) return;
		
		$convert = get_field( 'convert_to_template', $post_id );
		
		if ( $convert ) {
			update_field( 'convert_to_template', false, $post_id );
			
			$args = array(
				'ID' => $post_id,
				'post_type' => AH_Itinerary_Template()->get_post_type(),
			);
			
			$this->toggle_save_post_hooks(false);
			wp_update_post( $args );
			$this->toggle_save_post_hooks(true);
			
			AH_Admin()->add_notice( 'success', 'This itinerary has been converted to a template.', null, 'itinerary-convert', true );
		}
	}
	
	/**
	 * When saving the post, set the "User" field as the post author.
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	public function save_post_split_user_meta_keys( $post_id ) {
		if ( ! $this->is_valid( $post_id ) ) return;
		
		delete_post_meta( $post_id, 'assigned_user_id' );
		
		$user_ids = get_field( 'user_ids', $post_id );
		
		if ( $user_ids ) foreach( $user_ids as $user_id ) {
			add_post_meta( $post_id, 'assigned_user_id', $user_id );
		}
	}
	
	/**
	 * Generate table of contents for an itinerary, an array of items that can be used in a nested list
	 * Each item contains: title, link, children
	 *
	 * @param $itinerary_id
	 *
	 * @return array
	 */
	public function get_table_of_contents( $itinerary_id ) {
		$toc_list = array();
		
		// Itinerary
		$this->_add_toc_item( $toc_list, 'Introduction', '#intro' );
		
		$this->_add_toc_item( $toc_list, 'Schedule', '#schedule' );
		
		$this->_add_toc_item( $toc_list, 'Directory', '#directory' );
		
		$this->_add_toc_item( $toc_list, 'Tour Overview', '#tour-overview' );
		
		
		// Villages
		$villages = get_field( 'villages', $itinerary_id );
		
		if ( $villages ) {
			$this->_add_toc_item( $toc_list, 'Villages', '#villages', $village_list );
			
			foreach( $villages as $s ) {
				$village_id = (int) $s['village'];
				$title = get_the_title( $village_id );
				$link = '#village-' . esc_attr(get_post_field( 'post_name', $village_id ));
				$this->_add_toc_item( $village_list['children'], $title, $link );
			}
		}
		
		
		// Hikes
		$hikes = get_field( 'hikes', $itinerary_id );
		
		if ( $hikes ) {
			$this->_add_toc_item( $toc_list, 'Hikes', '#hikes', $hike_list );
			
			foreach( $hikes as $s ) {
				$hike_id = (int) $s['hike'];
				$title = get_the_title( $hike_id );
				$link = '#hike-' . esc_attr(get_post_field( 'post_name', $hike_id ));
				$this->_add_toc_item( $hike_list['children'], $title, $link );
			}
		}
		
		
		// Documents
		$attached_documents = get_field( 'attached_documents', $itinerary_id );
		
		if ( $attached_documents ) {
			$this->_add_toc_item( $toc_list, 'Documents', '#documents', $document_list );
			
			foreach( $attached_documents as $document_id ) {
				$title = get_the_title( $document_id );
				$link = '#document-' . $document_id;
				$this->_add_toc_item( $document_list['children'], $title, $link );
			}
		}
		
		return $toc_list;
	}
	
	/**
	 * Add an item to the table of contents, @see get_table_of_contents()
	 *
	 * @internal
	 *
	 * @param $list
	 * @param $title
	 * @param $link
	 * @param $new_item
	 *
	 * @return void
	 */
	public function _add_toc_item( &$list, $title, $link, &$new_item = array() ) {
		$new_item = array(
			'title' => $title,
			'link' => $link,
			'children' => array(),
		);
		
		$list[] = &$new_item;
	}
	
}