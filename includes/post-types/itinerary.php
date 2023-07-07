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
		
		// Custom page template
		add_filter( 'single_template', array( $this, 'replace_page_template' ) );
		
		// Display a <select> to pick a sheet for an itinerary
		add_action( 'add_meta_boxes', array( $this, 'register_smartsheet_meta_box' ), 5 );
		
		// Save the <select> containing smartsheet ID for the itinerary, when the post is saved
		add_action( 'save_post', array( $this, 'save_smartsheet_id' ) );
		
		// MOVED TO SYNC URL METHOD
		// Sync an itinerary after post updated
		// add_action( 'save_post', array( $this, 'sync_itinerary_with_sheet' ), 100 );
		
		// Filter hotel dropdown by village on itinerary edit screen
		// Applies to the Hotel dropdown in the Villages field group when editing an itinerary
		add_filter('acf/fields/post_object/query', array( $this, 'filter_hotel_dropdown_by_village_id' ), 10, 3);
		
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
		<div class="notice notice-info is-dismissible">
			<p>Would you like to load information from an itinerary template?</p>
			
			<form action="post-new.php?post_type=<?php echo $this->get_post_type(); ?>" method="POST">
				
				<input type="hidden" name="ah_action" value="load_itinerary_template" >
				<input type="hidden" name="itinerary_id" value="<?php echo get_the_ID(); ?>" >
				
				<p>
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
					
					<input type="submit" class="button button-secondary" value="Load Template">
				</p>
				
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
		$args['menu_position'] = 21;
		
		$args['publicly_queryable'] = true;
		$args['rewrite'] = array(
			'slug' => 'itinerary',
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
			// add_action( 'acf/save_post', array( $this, 'save_post_convert_to_template' ), 50 );
		}else{
			remove_action( 'acf/save_post', array( $this, 'save_post_split_user_meta_keys' ), 40 );
			
			// remove_action( 'acf/save_post', array( $this, 'save_post_convert_to_template' ), 50 );
		}
	}
	
	/**
	 * Convert to itinerary template when "Convert to Template" is checked.
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	/*
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
	*/
	
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
	 * Get the itinerary title from an acf custom field, or the post title if the custom field is empty
	 *
	 * @param int|null $post_id
	 *
	 * @return string
	 */
	public function get_itinerary_title( $post_id = null ) {
		if ( $post_id === null ) $post_id = get_the_ID();
		
		$title = get_field( 'title', $post_id );
		return $title ?: get_the_title($post_id);
	}
	
	/**
	 * Get an array of all settings for an itinerary, including custom field data and menu structure (used for table of contents)
	 *
	 * @param $itinerary_id
	 *
	 * @return array[] {
	 *     @type array $data
	 *     @type array $pages
	 * }
	 */
	public function get_itinerary_settings( $itinerary_id ) {
		
		// schedule is now empty if blank
		// all_phone_numbers -> phone_numbers
		// has_phone_numbers removed
		// menu items had link changed to "id" without #
		// added show_hikes
		// added show_documents
		// attached_documents -> documents
		
		$data = array();
		$pages = array();
		
		// ------------------------------
		// Data
		
		// External
		$data['slug'] = get_post_field( 'post_name', $itinerary_id );
		$data['logo_id'] = (int) get_field( 'white_logo', 'ah_settings' );
		
		// Itinerary Details
		$data['title'] = get_field( 'title', $itinerary_id );
		$data['subtitle'] = get_field( 'subtitle', $itinerary_id );
		$data['introduction_message'] = get_field( 'introduction_message', $itinerary_id );
		$data['contact_information'] = get_field( 'contact_information', $itinerary_id );
		$data['date_range'] = get_field( 'date_range', $itinerary_id );
		
		// [repeater] schedule: column_1, column_2, column_3
		$data['schedule'] = (array) get_field( 'schedule', $itinerary_id );
		if ( ah_is_array_recursively_empty($data['schedule']) ) $data['schedule'] = array();
		
		$data['departure_information'] = get_field( 'departure_information', $itinerary_id );
		
		// [repeater] phone_numbers: title, phone_number, content
		$data['phone_numbers'] = (array) get_field( 'phone_numbers', $itinerary_id );
		if ( ah_is_array_recursively_empty($data['phone_numbers']) ) $data['phone_numbers'] = array();
		
		$data['country_codes'] = get_field( 'country_codes', $itinerary_id );
		
		$data['tour_overview'] = get_field( 'tour_overview', $itinerary_id );
		
		// Hike Summary
		$data['hike_summary'] = ah_get_hike_summary( $itinerary_id );
		
		// Itinerary - Villages
		// [repeater] villages: village, hotel, add_text, content
		$data['villages'] = (array) get_field( 'villages', $itinerary_id );
		if ( ah_is_array_recursively_empty($data['villages']) ) $data['villages'] = array();
		
		// Itinerary - Hikes
		// [repeater] hikes: hike, add_text, content
		$data['hikes'] = (array) get_field( 'hikes', $itinerary_id );
		if ( ah_is_array_recursively_empty($data['hikes']) ) $data['hikes'] = array();
		
		// Itinerary - Documents
		$data['documents'] = array();
		
		// -- Itinerary Documents
		$it_docs = get_field( 'itinerary_documents', $itinerary_id );
		if ( $it_docs ) foreach( $it_docs as $d ) {
			if ( ! $d['file'] ) continue;
			
			$document_title = $d['title'];
			$image_id = $d['file'];
			$text = $d['text'];
			$url = get_attached_file( $image_id );
			
			$data['documents'][] = array(
				'title' => $document_title,
				'slug' => sanitize_title_with_dashes($document_title),
				'image_id' => $image_id,
				'url' => $url,
				'text' => $text,
			);
		}
		
		// -- Client Documents
		$cl_docs = get_field( 'attached_documents', $itinerary_id );
		if ( $cl_docs ) foreach( $cl_docs as $document_id ) {
			if ( ! AH_Document()->is_valid( $document_id ) ) continue;
			
			$document_title = AH_Document()->get_document_title( $document_id );
			$image_id = ah_get_document_preview_image( $document_id );
			$url = ah_get_document_link( $document_id );
			$text = get_field( 'content', $document_id );
			
			$data['documents'][] = array(
				'title' => $document_title,
				'slug' => sanitize_title_with_dashes($document_title),
				'image_id' => $image_id,
				'url' => $url,
				'text' => $text,
			);
		}
		
		// ------------------------------
		// Pages
		$pages['introduction'] = $this->add_itinerary_page(
			'Introduction',
			'introduction',
			(bool) ( $data['introduction_message'] || $data['contact_information'] )
		);
		
		$pages['schedule'] = $this->add_itinerary_page(
			'Schedule',
			'schedule',
			(bool) ( $data['schedule'] || $data['departure_information'] || ! $pages['introduction']['enabled'] )
		);;
		
		$pages['directory'] = $this->add_itinerary_page(
			'Directory',
			'directory',
			(bool) ( $data['phone_numbers'] || $data['country_codes'] )
		);;
		
		$pages['tour_overview'] = $this->add_itinerary_page(
			'Tour Overview',
			'tour-overview',
			(bool) ( $data['tour_overview'] )
		);;
		
		$pages['hike_summary'] = $this->add_itinerary_page(
			'Hike Summary',
			'hike-summary',
			(bool) ( $data['hike_summary'] )
		);;
		
		$pages['villages'] = $this->add_itinerary_page(
			'Villages',
			'villages',
			(bool) ( $data['villages'] )
		);;
		
		$pages['hikes'] = $this->add_itinerary_page(
			'Hikes',
			'hikes',
			(bool) ( $data['hikes'] )
		);;
		
		$pages['documents'] = $this->add_itinerary_page(
			'Documents',
			'documents',
			(bool) ( $data['documents'] )
		);;
		
		// Pages - Village Submenu
		if ( $pages['villages']['enabled'] ) {
			foreach( $data['villages'] as $s ) {
				$village_id = (int) $s['village'];
				if ( ! $village_id ) continue;
				
				$title = get_field( 'village_name', $village_id ) ?: get_the_title( $village_id );
				$id = 'village-' . esc_attr(get_post_field( 'post_name', $village_id ));
				$pages['villages']['children'][ $village_id ] = $this->add_itinerary_page( $title, $id );
			}
		}
		
		// Pages - Hikes Submenu
		if ( $pages['hikes']['enabled'] ) {
			foreach( $data['hikes'] as $s ) {
				$hike_id = (int) $s['hike'];
				if ( ! $hike_id ) continue;
				
				$title = get_field( 'hike_name', $hike_id ) ?: get_the_title( $hike_id );
				$id = 'hike-' . esc_attr(get_post_field( 'post_name', $hike_id ));
				$pages['hikes']['children'][ $hike_id ] = $this->add_itinerary_page( $title, $id );
			}
		}
		
		// Pages - Documents Submenu
		if ( $pages['documents']['enabled'] ) {
			foreach( $data['documents'] as $d ) {
				$title = $d['title'];
				$id = 'document-' . $d['slug'];
				
				$pages['documents']['children'][] = $this->add_itinerary_page( $title, $id );
			}
		}
		
		return array(
			'data' => $data,
			'pages' => $pages,
		);
	}
	
	/**
	 * Add a page used in @see get_itinerary_settings()
	 *
	 * @param string $title
	 * @param string $id
	 * @param bool $enabled
	 *
	 * @return array
	 */
	private function add_itinerary_page( $title, $id, $enabled = true ) {
		return array(
			'title' => (string) $title,
			'id' => (string) $id,
			'enabled' => (bool) $enabled,
			'children' => array(),
		);
	}
	
	/**
	 * Displays a list of <li> menu items (without <ul>) for the table of contents
	 *
	 * @param array $pages
	 *
	 * @return void
	 */
	public function display_table_of_contents( $pages ) {
		foreach( $pages as $p ) {
			$this->_display_toc_item( $p, 0 );
		}
	}
	
	/**
	 * Display a single table of contents item, and recurse for children items
	 *
	 * @param $item
	 *
	 * @return void
	 */
	public function _display_toc_item( $item ) {
		$title = $item['title'];
		$id = $item['id'];
		$enabled = $item['enabled'];
		$children = $item['children'];
		if ( ! $enabled ) return;
		
		$classes = 'menu-item';
		if ( $children ) $classes .= ' menu-item-has-children';
		
		?>
		<li class="<?php echo esc_attr( $classes ); ?>">
			
			<a href="#<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></a>
			
			<?php
			if ( $children ) {
				?>
				<ul class="sub-menu">
					<?php
					foreach( $children as $child_item ) {
						$this->_display_toc_item( $child_item );
					}
					?>
				</ul>
				<?php
			}
			?>
		
		</li>
		<?php
	}
	
	// Show meta box
	public function register_smartsheet_meta_box() {
		add_meta_box(
			'ah_itinerary_smartsheet_meta_box',
			'Smartsheet Sync',
			array( $this, 'display_smartsheet_meta_box' ),
			'ah_itinerary',
			'side'
		);
	}
	
	// Display meta box html
	public function display_smartsheet_meta_box() {
		$post_id = get_the_ID();
		$sheet_id = (string) get_post_meta( $post_id, 'smartsheet_sheet_id', true );
		$sheet_url = (string) get_post_meta( $post_id, 'smartsheet_sheet_url', true );
		
		$label = '';
		
		if ( $sheet_id ) {
			// Load current value
			$sheets = AH_Smartsheet_Sync_Sheets()->get_stored_sheet_list();
			$sheet = ah_find_in_array($sheets, 'id', $sheet_id);
			if ( $sheet ) {
				$label = $sheet['name'];
			}
		}
		
		echo '<p><label for="smartsheet_sheet_id"><strong>Itinerary Spreadsheet:</strong></label></p>';
		
		echo AH_Smartsheet_Sheet_Select()->get_select_html(array(
			'name' => 'smartsheet_sheet_id',
			'id' => 'smartsheet_sheet_id',
			'value' => $sheet_id,
			'label' => $label,
		));
		
		
		if ( $sheet_url ) {
			echo '<div id="sync-itinerary-controls">';
			echo '<p>';
			$sync_url = AH_Smartsheet_Sync()->get_sync_item_url( $post_id );
			echo '<a href="'. esc_attr($sync_url) .'" class="button button-secondary">Run Sync</a> ';
			
			echo ah_create_html_link( $sheet_url, 'View Spreadsheet' );
			echo '</p>';
			
			$last_sync = get_post_meta( $post_id, 'smartsheet_last_sync', true );
			echo '<p><span class="ah-last-sync">Last sync: ' . (ah_get_relative_date_html( $last_sync ) ?: '(never)') . '</span></p>';
			echo '</div>';
			
			?>
			<script type="text/javascript">
			jQuery(function() {
				var start_id = <?php echo json_encode($sheet_id); ?>;
				jQuery('#smartsheet_sheet_id').on('change', function() {
					var new_id = jQuery(this).val();
					var changed = start_id !== new_id;
					
					// Hide controls if the sheet ID changed
					jQuery('#sync-itinerary-controls').css( 'display', (changed ? 'none' : 'block') );
				});
			});
			</script>
			<?php
		}
		
	}
	
	// Save meta box fields
	public function save_smartsheet_id( $post_id ) {
		if ( get_post_type($post_id) != $this->get_post_type() ) return;
		
		// Save the sheet ID even if it is blank, as long as it was sent in the POST request.
		$sheet_id = isset($_POST['smartsheet_sheet_id']) ? stripslashes($_POST['smartsheet_sheet_id']) : null;
		
		if ( $sheet_id !== null ) {
			update_post_meta( $post_id, 'smartsheet_sheet_id', $sheet_id );
			
			// Also save the sheet name and URL after looking it up
			$sheet = $sheet_id ? AH_Smartsheet_Sync_Sheets()->get_sheet_data( $sheet_id ) : false;
			
			if ( $sheet ) {
				update_post_meta( $post_id, 'smartsheet_sheet_name', $sheet['sheet_name'] );
				update_post_meta( $post_id, 'smartsheet_sheet_url', $sheet['permalink'] );
			}
		}
		
	}
	
	// MOVED TO SYNC URL
	// Sync an itinerary after post updated
	/*
	public function sync_itinerary_with_sheet( $post_id ) {
		// Check if "Run Sync" button was clicked
		// @todo: move this
		if ( ! isset($_POST['ah_save_and_sync_itinerary']) ) return;
		if ( get_post_type($post_id) != $this->get_post_type() ) return;
		
		$sheet_id = get_post_meta( $post_id, 'smartsheet_sheet_id', true );
		
		if ( $sheet_id ) {
			AH_Smartsheet_Sync_Itineraries()->sync_itinerary_with_sheet( $post_id, $sheet_id );
		}
	}
	*/
	
	// Filter hotel dropdown by village on itinerary edit screen
	function filter_hotel_dropdown_by_village_id( $args, $field, $post_id ) {
		// hotel: field_6438875876dfa
		// village: field_641a98e77d31a
		
		// Check that we are loading results for the HOTEL dropdown
		if ( $field['key'] !== 'field_6438875876dfa' ) return $args;
		
		// Get the village ID that was selected in a different field
		// This is passed by admin.js -> link_hotel_and_village_dropdowns()
		$village_id = isset($_POST['village_id']) ? stripslashes($_POST['village_id']) : false;
		
		// Modify the query args to include 'village_id' meta query
		if ( $village_id ) {
			$args['meta_query'] = array(
				array(
					'key' => 'village',
					'value' => $village_id,
					'compare' => '='
				)
			);
		}
		
		return $args;
	}
	
}