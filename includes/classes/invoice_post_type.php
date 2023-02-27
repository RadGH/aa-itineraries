<?php

class Class_Invoice_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_invoice';
	
	public $use_custom_title = true;
	public $use_custom_slug = true;
	
	// payment form ID
	public $form_id = 9;
	
	// payment form field IDs
	public $field_ids = array(
		
		'invoice_id'        => 14,
		'is_user_logged_in' => 16,
		
		'first_name'        => '1.3',
		'last_name'         => '1.6',
		
		'email'             => 2,
		'phone_number'      => 4,
		
		'username'          => 19, // if logged out
		'password'          => 18, // if logged out
		
		'address'           => '3.1',
		'address_2'         => '3.2',
		'city'              => '3.3',
		'state'             => '3.4',
		'zip'               => '3.5',
		'country'           => '3.6',
		
		'tour_date'         => 6,
		
		'payment_amount'    => 9, // currency number input
		'price'             => 11, // calculated from payment_amount
		'credit_card'       => 10,
		
	);
	
	// Fields from the entry that are saved to the post.
	// These MUST exist in $this->field_ids
	public $gf_invoice_fields = array(
		'first_name',
		'last_name',
		'email',
		'phone_number',
		'address',
		'address_2',
		'city',
		'state',
		'zip',
		'country',
	);
	
	public function __construct() {
		
		parent::__construct();
		
		// Only allow access to invoice if you own the invoice
		add_action( 'template_redirect', array( $this, 'restrict_invoice_access' ) );
		
		// When an entry is created, create or update an invoice
		add_action( 'gform_entry_created', array( $this, 'gf_entry_create_or_edit_invoice' ), 5, 2 );
		
		// When an invoice payment has been completed, mark the invoice status as paid
		add_action( 'gform_post_payment_completed', array( $this, 'gf_after_invoice_payment' ), 5, 2 );
		
		// Fill hidden fields on the form with calculated data
		add_filter( "gform_field_value_is_user_logged_in", array( $this, 'gf_fill_is_user_logged_in' ), 20, 3 );
		
		// Fill default values for GF
		add_filter( 'shortcode_atts_gravityforms', array( $this, 'gf_fill_field_values' ), 20, 4 );
		
		// When status changes to Processing, start a timer to automatically mark as Payment Failed after some duration
		add_filter( 'acf/update_value/name=status', array( $this, 'acf_set_processing_start_date' ), 40, 3 );
		
	}
	
	public function get_form_id() {
		return $this->form_id;
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
		$args['labels']['name_admin_bar'] = 'Invoices';
		
		$args['labels']['add_new_item'] = 'Add New Invoice';
		$args['labels']['all_items'] = 'All Invoices';
		$args['labels']['add_new'] = 'Add Invoice';
		$args['labels']['new_item'] = 'New Invoice';
		$args['labels']['edit_item'] = 'Edit Invoice';
		$args['labels']['update_item'] = 'Update Invoice';
		$args['labels']['view_item'] = 'View Invoice';
		
		$args['menu_icon'] = 'dashicons-media-spreadsheet';
		$args['menu_position'] = 20;
		
		$args['publicly_queryable'] = true;
		$args['rewrite'] = array(
			'slug' => 'account/invoice',
			'with_front' => false,
		);
		
		$args['hierarchical'] = false;
		$args['supports'] = array( 'title', 'author' );
		
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
			array('ah_status' => 'Status'),
			array('ah_amount' => 'Amount'),
			array('ah_name' => 'Name'),
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
			case 'ah_status':
				echo $this->get_invoice_status( $post_id );
				break;
			case 'ah_amount':
				echo $this->get_payment_amount( $post_id, true );
				break;
			case 'ah_name':
				echo get_field( 'first_name', $post_id );
				echo ' ';
				echo get_field( 'last_name', $post_id );
				break;
		}
	}
	
	public function restrict_invoice_access() {
		pre_dump(is_singular( $this->get_post_type() ));
		exit;
		
		// Only affect singular invoice page
		if ( ! is_singular( $this->get_post_type() ) ) return;
		
		$invoice_id = get_the_ID();
		
		// Check if the user is the invoice owner
		$current_user_id = get_current_user_id();
		$invoice_user_id = $this->get_owner( $invoice_id );
		
		// Allow owner to see their own invoice
		if ( $current_user_id == $invoice_user_id ) return;
		
		// Allow admins to see any invoice
		if ( current_user_can( 'administrator' ) ) return;
		
		// Block any other access
		get_template_part( '404' );
		exit;
		
	}
	
	/**
	 * When an entry is created, create or update an invoice
	 *
	 * @param $entry
	 * @param $form
	 *
	 * @return void
	 */
	public function gf_entry_create_or_edit_invoice( $entry, $form ) {
		if ( $form['id'] != $this->get_form_id() ) return;
		
		// Get invoice ID, if updating an existing one
		$invoice_id = (int) rgar( $entry, $this->field_ids['invoice_id'] );
		
		if ( ! $invoice_id ) {
			
			// Create an invoice if not already present
			$invoice_id = $this->create_invoice();
			if ( ! $invoice_id ) aa_die( 'Could not create invoice for this payment. Your payment may still be processed. Please contact us for support', compact('entry'));
			
			// Set invoice status as processing
			$this->set_invoice_status( $invoice_id, 'Processing' );
			
		}else{
			
			// Verify the existing invoice
			if ( get_post_type( $invoice_id ) != $this->get_post_type() ) return;
			
		}
		
		// Add log message
		$this->add_log_message( $invoice_id, 'Entry #' . $entry['id'] . ' was submitted' );
		
		// Add entry fields to the invoice
		foreach( $this->gf_invoice_fields as $meta_key ) {
			$value = $this->get_entry_value( $entry, $meta_key );
			
			update_field( $meta_key, $value, $invoice_id );
		}
		
		// Save additional fields
		update_field( 'tour_date', $this->get_entry_value($entry, 'tour_date'), $invoice_id );
		
		// Store the post on the entry, and vice versa
		gform_update_meta( $entry['id'], $this->field_ids['invoice_id'], $invoice_id );
		update_post_meta( $invoice_id, 'entry_id', $entry['id'] );
	}
	
	/**
	 * When an invoice payment has been completed, mark the invoice status as paid
	 *
	 * @param array $entry
	 * @param array $action includes: type, amount, transaction_type, transaction_id,
	 *                      subscription_id, entry_id, payment_status, note
	 *
	 * @return void
	 */
	public function gf_after_invoice_payment( $entry, $action ) {
		if ( $entry['form_id'] != $this->get_form_id() ) return;
		
		// Save the payment amount to the invoice
		$post_id = $this->get_invoice_id_from_entry_id( $entry );
		$payment_amount = $this->get_entry_value($entry, 'payment_amount');
		
		if ( $post_id && $payment_amount ) {
			$this->apply_payment_amount( $post_id, $payment_amount );
		}
	}
	
	/**
	 * Gravity form field with parameter name "is_user_logged_in" will have the value set
	 * to 0 or 1 when viewing the form, based on if they are logged in or not.
	 *
	 * @param $value
	 * @param $field
	 * @param $name
	 *
	 * @return int
	 */
	public function gf_fill_is_user_logged_in( $value, $field, $name ) {
		return is_user_logged_in() ? 1 : 0;
	}
	
	/**
	 * Fill payment form fields with values from the invoice (if the form is for an existing invoice)
	 *
	 * @param $atts
	 * @param $pairs
	 * @param $default_atts
	 * @param $shortcode_name
	 *
	 * @return mixed
	 */
	public function gf_fill_field_values( $atts, $pairs, $default_atts, $shortcode_name ) {
		
		// [gravityform id="9" field_values="parameter_name1=value1&parameter_name2=value2"]
		// $atts['id'] = "9"
		// $atts['field_values'] = ""
		
		if ( $atts['id'] != $this->get_form_id() ) return $atts;
		if ( $atts['action'] != 'form' ) return $atts;
		
		// Get the invoice ID that the user is about to pay
		$invoice_id = isset($_GET['invoice_id']) ? (int) $_GET['invoice_id'] : false;
		if ( ! $this->is_valid_invoice( $invoice_id ) ) return $atts;
		
		// Pull values from the invoice
		$field_values = wp_parse_args( (string) $atts['field_values'] );
		$field_value_str = '';
		
		foreach( $this->gf_invoice_fields as $meta_key ) {
			// Keep values that were already inserted
			if ( isset($field_values[ $meta_key ]) ) continue;
			
			// Get value from invoice
			$value = get_field( $meta_key, $invoice_id );
			
			// Add field value to output string
			// * may need escaping for = and &
			if ( $field_value_str ) $field_value_str .= '&';
			$field_value_str .= esc_attr($meta_key) . '=' . esc_attr($value);
		}
		
		$atts['field_values'] = $field_value_str;
		
		return $atts;
	}
	
	
	/**
	 * When status changes to Processing, start a timer to automatically mark as Payment Failed after some duration
	 *
	 * @param $value
	 * @param $object_id
	 * @param $field
	 *
	 * @return void
	 */
	public function acf_set_processing_start_date( $value, $object_id, $field ) {
		$info = acf_get_post_id_info( $object_id );
		if ( $info['type'] != 'post' ) return $value;
		
		$post_id = $info['id'];
		if ( get_post_type( $post_id ) != $this->get_post_type() ) return $value;
		
		if ( $value == 'Processing' ) {
			// Store the date this started processing. Mark as payment failed after a certain amount of time.
			update_post_meta( $post_id, 'processing_start_date', date('Y-m-d H:i:s') );
		}else{
			delete_post_meta( $post_id, 'processing_start_date' );
		}
	}
	
	/**
	 * Set the status of an invoice. Also starts a timer if the status is processing, or ends the timer otherwise.
	 *
	 * @param int $post_id
	 * @param string $new_status
	 * @param bool $add_to_log
	 *
	 * @return bool
	 */
	public function set_invoice_status( $post_id, $new_status, $add_to_log = true ) {
		$current_status = $this->get_invoice_status( $post_id );
		
		// No change needed
		if ( $current_status == $new_status ) return false;
		
		// Save the status
		update_post_meta( $post_id, 'invoice_status', $new_status );
		
		// Add log message
		if ( $add_to_log ) {
			$this->add_log_message( $post_id, 'Invoice status set to "' . $new_status . '" and was previously "' . $current_status . '"' );
		}
		
		// Save the date that processing started, or clear it otherwise.
		if ( $new_status == 'Processing' ) {
			update_post_meta( $post_id, 'processing_start_date', date('Y-m-d H:i:s') );
		}else{
			delete_post_meta( $post_id, 'processing_start_date' );
		}
		
		return true;
	}
	
	/**
	 * Get the status of an invoice.
	 *
	 * @param int $post_id
	 *
	 * @return string|false
	 */
	public function get_invoice_status( $post_id ) {
		$status = (string) get_post_meta( $post_id, 'invoice_status', true );
		
		return $status ?: false;
	}
	
	public function set_amount_due( $invoice_id, $amount ) { update_field( 'amount_due', $amount, $invoice_id ); }
	public function get_amount_due( $invoice_id ) { return (float) get_field( 'amount_due', $invoice_id ); }
	
	public function set_due_date( $invoice_id, $due_date ) { update_field( 'due_date', $due_date, $invoice_id ); }
	public function get_due_date( $invoice_id ) { return get_field( 'due_date', $invoice_id ); }
	
	public function set_payment_amount( $invoice_id, $amount ) { update_field( 'payment_amount', $amount, $invoice_id ); }
	 
	/**
	 * Get the payment amount of an invoice.
	 * If $currency_format is true, returns formatted as USD currency.
	 *
	 * @param int $post_id
	 * @param bool $currency_format
	 *
	 * @return string|false
	 */
	public function get_payment_amount( $post_id, $currency_format = false ) {
		$amount = get_post_meta( $post_id, 'payment_amount', true );
		
		if ( $amount && $currency_format ) {
			$amount = '$' . number_format(  (float) $amount, 2 );
		}
		
		return $amount ?: false;
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
		if ( ! $this->is_valid_invoice( $invoice_id ) ) return;
		
		// Save as custom field
		update_field( 'user', $user_id );
		
		// Also save as the post author
		$args = array(
			'ID' => $invoice_id,
			'post_author' => $user_id,
		);
		
		// Unhook and re-hook to avoid infinite loop
		$this->toggle_save_post_hooks(false);
		wp_update_post($args);
		$this->toggle_save_post_hooks(true);
	}
	
	/**
	 * Get the owner of an invoice (user ID)
	 *
	 * @param $invoice_id
	 *
	 * @return int|false
	 */
	public function get_owner( $invoice_id ) {
		$user_id = get_field( 'user', $invoice_id );
		
		// If not defined, use the post author by default
		if ( empty($user_id) ) {
			$post = get_post( $invoice_id );
			if ( ! $post || $post->post_type != $this->get_post_type() ) return false;
			
			if ( $post->post_author ) {
				$user_id = $post->post_author;
				$this->set_owner( $invoice_id, $user_id );
			}
		}
		
		return $user_id ?: false;
	}
	
	/**
	 * Get status indicator HTML to show next to the status. This is a colored bubble.
	 *
	 * @param $post_id
	 *
	 * @return string
	 */
	public function get_invoice_status_indicator( $post_id ) {
		$status = $this->get_invoice_status( $post_id );
		$status_slug = sanitize_title_with_dashes( strtolower( $status) );
		return '<span class="ah-invoice-status-indicator status-'. $status_slug .'"></span>';
	}
	
	/**
	 * Get the invoice post ID from a gravity form entry
	 *
	 * @param array $entry
	 *
	 * @return false|int
	 */
	public function get_invoice_id_from_entry_id( $entry ) {
		if ( $entry['form_id'] != $this->get_form_id() ) return false;
		
		$post_id = (int) gform_get_meta( $entry['id'], $this->field_ids['invoice_id'] );
		
		if ( $this->is_valid_invoice( $post_id ) ) {
			return $post_id;
		}else{
			return false;
		}
	}
	
	/**
	 * Get an entry array from an invoice post ID
	 *
	 * @param $post_id
	 *
	 * @return array|false
	 */
	public function get_entry_id_from_invoice_id( $post_id ) {
		if ( get_post_type( $post_id ) != $this->get_post_type() ) return false;
		
		$entry_id = (int) get_post_meta( $post_id, 'entry_id', true );
		$entry = GFAPI::get_entry( $entry_id );
		
		if ( $entry['form_id'] == $this->get_form_id() ) {
			return $entry;
		}else{
			return false;
		}
		
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
			'author' => (int) $user_id
		);
		
		return new WP_Query( $args );
	}
	
	/**
	 * Creates a plain invoice with some default values
	 *
	 * @param $custom_args
	 *
	 * @return false|int
	 */
	public function create_invoice( $custom_args = null ) {
		// Default invoice args
		$args = array(
			'post_title' => 'New Invoice',
			'post_author' => get_current_user_id(),
			'post_type' => $this->get_post_type(),
			'post_name' => $this->get_custom_post_slug(),
			'post_status' => 'publish',
		);
		
		// Apply custom args
		if ( $custom_args !== null ) {
			$args = wp_parse_args( $args, $custom_args );
		}
		
		// Create post
		$post_id = wp_insert_post( $args );
		if ( ! $post_id || is_wp_error( $post_id ) ) return false;
		
		// Regenerate title based on the ID
		$this->set_custom_post_title( $post_id );
		
		// Save default custom fields
		$this->set_invoice_status( $post_id, 'Awaiting Payment', false );
		$this->set_amount_due( $post_id, 0 );
		$this->set_payment_amount( $post_id, 0 );
		$this->set_due_date( $post_id, ah_adjust_date( '+30 days', 'Y-m-d' ) );
		
		// Add log message
		$this->add_log_message( $post_id, 'Invoice created' );
		
		return (int) $post_id;
	}
	
	/**
	 * Add a line to the log for an invoice
	 *
	 * @param $invoice_id
	 * @param $message
	 *
	 * @return void
	 */
	public function add_log_message( $invoice_id, $message ) {
		$message = current_time( 'm/d/Y g:i:s a' ) . ': ' . $message;
		
		$log = (string) get_field( 'log', $invoice_id );
		if ( $log ) $log .= "\n";
		$log .= $message;
		
		update_field( 'log', $log, $invoice_id );
	}
	
	/**
	 * Check if an invoice is valid (exists, correct post type)
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	public function is_valid_invoice( $post_id ) {
		return get_post_type( $post_id ) == $this->get_post_type();
	}
	
	/**
	 * Get a value from an entry using a key like "first_name" instead of a field id from $this->field_ids
	 *
	 * @param $entry
	 * @param $meta_key
	 *
	 * @return false
	 */
	public function get_entry_value( $entry, $meta_key ) {
		$field_id = $this->field_ids[ $meta_key ] ?? false;
		if ( ! $field_id ) return false;
		
		return $entry[ $field_id ] ?? false;
	}
	
	/**
	 * Get URL to an invoice page (NOT the form)
	 *
	 * @param $post_id
	 *
	 * @return string|false
	 */
	public function get_invoice_page_url( $post_id ) {
		if ( ! $this->is_valid_invoice( $post_id ) ) return false;
		return get_permalink( $post_id );
	}
	
	/**
	 * Get URL to the payment form for a specific invoice
	 *
	 * @param $post_id
	 *
	 * @return string|false
	 */
	public function get_invoice_form_url( $post_id ) {
		if ( ! $this->is_valid_invoice( $post_id ) ) return false;
		$invoice_page_id = ah_get_invoice_page_id();
		$invoice_page_url = get_permalink( $invoice_page_id );
		return add_query_arg( array( 'invoice_id' => (int) $post_id ), $invoice_page_url );
	}
	
	/**
	 * Return true if an invoice needs payment
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public function does_invoice_need_payment( $post_id ) {
		if ( ! $this->is_valid_invoice( $post_id ) ) return false;
		
		$status = $this->get_invoice_status( $post_id );
		if ( $status == 'Paid' ) return false;
		if ( $status == 'Processing' ) return false; // may need payment if the payment does not go through
		
		return true;
	}
	
	/**
	 * Apply a payment amount to an invoice, automatically changing the status to Paid.
	 * If not paid in full, status changes back to Awaiting Payment.
	 *
	 * @param $invoice_id
	 * @param $payment_amount
	 *
	 * @return void
	 */
	public function apply_payment_amount( $invoice_id, $payment_amount ) {
		// Get amount due and amount currently paid
		$total_due = (float) get_field( 'amount_due', $invoice_id, false );
		$total_paid = (float) get_field( 'payment_amount', $invoice_id, false );
		
		// Add the payment to the amount paid
		$total_paid += $payment_amount;
		update_field( 'payment_amount', $total_paid, $invoice_id );
		
		// Show a message in the log about that payment
		$total_paid_formatted = '$' . number_format(  (float) $total_paid, 2 );
		$this->add_log_message( $invoice_id, 'Applied payment of ' . $total_paid_formatted );
		
		// Change the status to Paid if it is now paid, or Awaiting Payment if it is still not paid in full
		if ( $total_paid >= $total_due ) {
			$this->set_invoice_status( $invoice_id, 'Paid' );
		}else{
			$this->set_invoice_status( $invoice_id, 'Awaiting Payment' );
		}
	}
	
}