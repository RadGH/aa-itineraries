<?php

class Class_Invoice_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_invoice';
	
	public $use_custom_title = true;
	public $use_custom_slug = true;
	
	public function __construct() {
		
		parent::__construct();
		
		// Custom page template
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
		
		$owner_id = $this->get_owner( get_the_ID() );
		if ( $owner_id != $user_id ) {
			if ( current_user_can( 'administrator' ) ) {
				return true;
			}else{
				return false;
			}
		}
		
		return true;
	}
	
	public function replace_page_template( $template ) {
		global $post;
		
		if ( $post->post_type == $this->get_post_type() ) {
			$template = AH_PATH . '/templates/single-invoice.php';
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
		
		$args['label'] = 'Invoice';
		
		$args['labels']['name']           = 'Invoices';
		$args['labels']['singular_name']  = 'Invoice';
		$args['labels']['menu_name']      = 'Invoices';
		$args['labels']['name_admin_bar'] = 'Invoice';
		
		$args['labels']['add_new_item'] = 'Add New Invoice';
		$args['labels']['all_items'] = 'All Invoices';
		$args['labels']['add_new'] = 'Add Invoice';
		$args['labels']['new_item'] = 'New Invoice';
		$args['labels']['edit_item'] = 'Edit Invoice';
		$args['labels']['update_item'] = 'Update Invoice';
		$args['labels']['view_item'] = 'View Invoice';
		
		$args['menu_icon'] = 'dashicons-media-spreadsheet';
		$args['menu_position'] = 21;
		
		$args['publicly_queryable'] = true;
		$args['rewrite'] = array(
			'slug' => 'account/invoice',
			'with_front' => false,
		);
		
		$args['hierarchical'] = false;
		$args['supports'] = array( 'title' );
		
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
		if ( isset($columns['ssid']) ) unset($columns['ssid']);
		
		return array_merge(
			array_slice( $columns, 0, 2),
			array('ah_user' => 'Assigned To'),
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
			
			case 'ah_user':
				$user_id = $this->get_owner( $post_id );
				$user = $user_id ? get_user_by( 'id', $user_id ) : false;
				
				if ( $user ) {
					$name = ah_get_user_full_name( $user->ID );
					$url = get_edit_user_link( $user->ID );
					echo sprintf(
						'<a href="%s">%s</a>',
						esc_attr($url),
						esc_html($name)
					);
				}else{
					echo '<em style="opacity: 0.5;">Not assigned</em>';
				}
				
				break;
				
		}
	}
	
	/**
	 * Remove author metabox because we use a "User" field instead
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
			add_action( 'acf/save_post', array( $this, 'save_post_reassign_author' ), 40 );
		}else{
			remove_action( 'acf/save_post', array( $this, 'save_post_reassign_author' ), 40 );
		}
	}
	/**
	 * After post updated: Set custom title
	 *
	 * @param int $post_id
	 *
	 * @return void
	 */
	public function set_custom_post_title( $post_id ) {
		if ( ! $this->is_valid( $post_id ) ) return;
		
		// Keep existing post title
		if ( ! empty($post->post_title) ) return;
		
		$invoice_number = $this->get_invoice_number( $post_id );
		
		if ( $invoice_number ) {
			$post_title = 'Invoice #' . $invoice_number;
		}else{
			$post_title = 'New Invoice';
		}
		
		// If the title is different, update it
		$args = array(
			'ID' => $post_id,
			'post_title' => $post_title,
		);
		
		// Unhook and re-hook to avoid infinite loop
		$this->toggle_save_post_hooks(false);
		wp_update_post($args);
		$this->toggle_save_post_hooks(true);
	}
	
	/**
	 * When saving the post, set the "Assigned User" field as the post author.
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	public function save_post_reassign_author( $post_id ) {
		if ( ! $this->is_valid( $post_id ) ) return;
		
		$user_id = get_field( 'user', $post_id );
		if ( ! $user_id ) $user_id = false;
		
		$this->set_owner( $post_id, $user_id );
	}
	
	/**
	 * Get the quickbooks url of an invoice
	 *
	 * @param int $invoice_id
	 *
	 * @return string
	 */
	public function get_quickbooks_url( $invoice_id ) {
		return get_field( 'quickbooks_url', $invoice_id );
	}
	
	/**
	 * Get the invoice number of an invoice
	 *
	 * @param int $invoice_id
	 *
	 * @return string
	 */
	public function get_invoice_number( $invoice_id ) {
		return get_field( 'invoice_number', $invoice_id );
	}
	
	/**
	 * Change the owner of an invoice (who the invoice belongs to, which will show on their dashboard)
	 *
	 * @param int $invoice_id
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function set_owner( $invoice_id, $user_id ) {
		if ( ! $this->is_valid( $invoice_id ) ) return;
		
		// Save the user to custom field
		update_field( 'user', $user_id );
		
		// Save the post author
		parent::set_owner( $invoice_id, $user_id );
	}
	
	/**
	 * Get a WP_Query containing all of the user's invoices
	 *
	 * @param $user_id
	 *
	 * @return false|WP_Query
	 */
	public function get_user_invoices( $user_id = null ) {
		if ( $user_id === null ) $user_id = get_current_user_id();
		if ( ! $user_id ) return false;
		
		$args = array(
			'post_type' => $this->get_post_type(),
			'nopaging' => true,
			'author' => (int) $user_id,
		);
		
		return new WP_Query( $args );
	}
	
}