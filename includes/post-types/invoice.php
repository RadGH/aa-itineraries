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
		
		'amount_paid'       => 9, // currency number input
		'price'             => 11, // calculated from amount_paid
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
		// add_action( 'template_redirect', array( $this, 'restrict_invoice_access' ) );
		
		// Calculate reminder notifications when due date changes
		add_action( 'acf/save_post', array( $this, 'save_post_recalculate_reminders' ), 40 );
		
		// When an entry is created, create or update an invoice
		add_action( 'gform_entry_created', array( $this, 'gf_entry_create_or_edit_invoice' ), 5, 2 );
		
		// When an invoice payment has been completed, mark the invoice status as paid
		add_action( 'gform_post_payment_completed', array( $this, 'gf_after_invoice_payment' ), 5, 2 );
		
		// Fill hidden fields on the form with calculated data
		add_filter( "gform_field_value_is_user_logged_in", array( $this, 'gf_fill_is_user_logged_in' ), 20, 3 );
		
		// Fill default values for GF
		add_filter( 'shortcode_atts_gravityforms', array( $this, 'gf_fill_field_values' ), 20, 4 );
		
		// Custom page template
		add_filter( 'single_template', array( $this, 'replace_page_template' ) );
		
		// Displays merge tags for your most recent invoice
		// https://alpinehikers.com.com/?test_invoice_merge_tags
		// https://alpinehikerdev.wpengine.com/?test_invoice_merge_tags
		if ( isset($_GET['test_invoice_merge_tags']) ) add_action( 'init', array( $this, 'test_invoice_merge_tags' ) );
		
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
		return array_merge(
			array_slice( $columns, 0, 2),
			array('ah_status' => 'Status'),
			array('ah_amount' => 'Remaining Balance'),
			array('ah_name' => 'Name'),
			array('ah_owner' => 'Assigned To'),
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
				$amount = $this->get_remaining_balance( $post_id );
				echo ah_format_price( $amount );
				break;
				
			case 'ah_name':
				echo get_field( 'first_name', $post_id );
				echo ' ';
				echo get_field( 'last_name', $post_id );
				break;
				
			case 'ah_owner':
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
					echo '<em style="opacity: 0.5;">Nobody</em>';
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
	
	/*
	public function restrict_invoice_access() {
		
		// Only affect singular invoice page
		if ( ! is_singular( $this->get_post_type() ) ) return;
		
		$invoice_id = get_the_ID();
		
		// Check if the invoice has an owner.
		$invoice_user_id = $this->get_owner( $invoice_id );
		
		// Public invoices can be paid by anyone, though they must either log in or create an account
		if ( ! $invoice_user_id ) return;
		
		// Check if the user is the invoice owner
		$current_user_id = get_current_user_id();
		
		// Allow owner to see their own invoice
		if ( $current_user_id == $invoice_user_id ) return;
		
		// Allow admins to see any invoice
		if ( current_user_can( 'administrator' ) ) return;
		
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
	*/
	
	/**
	 * When saving the post, set the "User" field as the post author.
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
	 * Calculate reminder notifications when due date changes
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	public function save_post_recalculate_reminders( $post_id ) {
		if ( ! $this->is_valid( $post_id ) ) return;
		
		$this->setup_reminder_notifications( $post_id );
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
		$amount_paid = $this->get_entry_value($entry, 'amount_paid');
		
		if ( $post_id && $amount_paid ) {
			$this->apply_payment( $post_id, $amount_paid );
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
		if ( ! $this->is_valid( $invoice_id ) ) return $atts;
		
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
	public function get_amount_due( $post_id ) { return (float) get_post_meta( $post_id, 'amount_due', true ); }
	
	public function set_amount_paid( $invoice_id, $amount ) { update_field( 'amount_paid', $amount, $invoice_id ); }
	public function get_amount_paid( $post_id ) { return (float) get_post_meta( $post_id, 'amount_paid', true ); }

	// Remaining balance cannot be changed directly
	// public function set_get_remaining_balance( $invoice_id, $amount ) { exit; }
	public function get_remaining_balance( $post_id ) {
		$due = $this->get_amount_due( $post_id );
		$paid = $this->get_amount_paid( $post_id );
		return $due - $paid;
	}

	public function set_due_date( $invoice_id, $due_date ) { update_field( 'due_date', $due_date, $invoice_id ); }
	
	/**
	 * Get the due date of an invoice, optionally using a PHP date $format
	 *
	 * @param int $invoice_id
	 * @param string $format
	 *
	 * @return string|false     "Y-m-d" date or false
	 */
	public function get_due_date( $invoice_id, $format = 'Y-m-d' ) {
		$date_ymd = get_field( 'due_date', $invoice_id );
		if ( ! $date_ymd ) return false;
		return date($format, strtotime($date_ymd)); // reformat "Ymd" to "Y-m-d" for consistency
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
		
		if ( $this->is_valid( $post_id ) ) {
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
	 * @param $owner_id
	 * @param $custom_args
	 *
	 * @return false|int
	 */
	public function create_invoice( $owner_id = null, $custom_args = null ) {
		if ( $owner_id === null ) $owner_id = get_current_user_id();
		
		// Default invoice args
		$args = array(
			'post_title' => 'New Invoice',
			'post_author' => $owner_id,
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
		
		// Assign owner
		$this->set_owner( $post_id, $owner_id );
		
		// Regenerate title based on the ID
		$this->set_custom_post_title( $post_id );
		
		// Save default custom fields
		$this->set_invoice_status( $post_id, 'Awaiting Payment', false );
		$this->set_amount_due( $post_id, 0 );
		$this->set_amount_paid( $post_id, 0 );
		$this->set_due_date( $post_id, ah_adjust_date( '+30 days', 'Y-m-d' ) );
		
		// Add log message
		$this->add_log_message( $post_id, 'Invoice created' );
		
		// Set up reminder dates
		$this->setup_reminder_notifications( $post_id );
		
		return (int) $post_id;
	}
	
	/**
	 * Get an array of key:value pairs where the key is a merge tag like [first_name]
	 *
	 * @param int|string $invoice_id
	 *
	 * @return string[]
	 */
	public function get_merge_tags( $invoice_id = null ) {
		
		// Variables to use directly
		if ( $this->is_valid( $invoice_id ) ) {
			$invoice_status = $this->get_invoice_status( $invoice_id );
			$invoice_page_url = $this->get_invoice_page_url( $invoice_id );
			$invoice_form_url = $this->get_invoice_form_url( $invoice_id );
			
			$amount_due = $this->get_amount_due( $invoice_id );
			$amount_paid = $this->get_amount_paid( $invoice_id );
			$remaining_balance = $this->get_remaining_balance( $invoice_id );
			
			$due_date = $this->get_due_date( $invoice_id, 'm/d/Y' );
			$remaining_time = human_time_diff( $this->get_due_date( $invoice_id, 'U' ), current_time('U') );
			
			$first_name = get_post_meta( $invoice_id, 'first_name', true );
			$last_name = get_post_meta( $invoice_id, 'last_name', true );
			$full_name = trim( $first_name . ' ' . $last_name );
			
			$email = get_post_meta( $invoice_id, 'email', true );
			$phone_number = get_post_meta( $invoice_id, 'phone_number', true );
			
			$address = get_post_meta( $invoice_id, 'address', true );
			$address_2 = get_post_meta( $invoice_id, 'address_2', true );
			$city = get_post_meta( $invoice_id, 'city', true );
			$state = get_post_meta( $invoice_id, 'state', true );
			$zip = get_post_meta( $invoice_id, 'zip', true );
			$country = get_post_meta( $invoice_id, 'country', true );
			
			$tour_date = get_post_meta( $invoice_id, 'tour_date', true );
			
			// Use the user's first name, last name, and email, if not provided on the invoice
			$owner_user_id = $this->get_owner( $invoice_id );
			
			if ( $owner_user_id ) {
				if ( ! $first_name ) $first_name = ah_get_user_field( $owner_user_id, 'first_name');
				if ( ! $last_name ) $last_name = ah_get_user_field( $owner_user_id, 'last_name');
				if ( ! $email ) $email = ah_get_user_field( $owner_user_id, 'user_email');
			}
			
			// Format some values
			$amount_due = ah_format_price( $amount_due );
			$amount_paid = ah_format_price( $amount_paid );
			$remaining_balance = ah_format_price( $remaining_balance );
		}
		
		if ( $invoice_id === 'placeholders' ) {
			
			$invoice_id        = '5550';
			$invoice_page_url  = site_url('/account/invoice/ah-invoice-1234567b1470b/');
			$invoice_form_url  = site_url('/account/payment/?invoice_id=6074');
			
			$invoice_status    = 'Awaiting Payment';
			$amount_due        = '$50';
			$amount_paid       = '$0';
			$remaining_balance = '$50';
			
			$due_date          = date('m/d/Y', strtotime('+1 week'));
			$remaining_time    = '1 week';
			
			$first_name        = 'John';
			$last_name         = 'Smith';
			$full_name         = 'John Smith';
			
			$email             = 'jsmith@example.org';
			$phone_number      = '555-123-4567';
			
			$address           = '1234 Example Road';
			$address_2         = 'Apt 205b';
			$city              = 'Eugene';
			$state             = 'OR';
			$zip               = '97401';
			$country           = 'United States';
			
			$tour_date         = 'Summer 2023';
			
		}
		
		// Build merge tags array
		$tags = array(
			
			'[invoice_id]'        => $invoice_id         ?? "",
			'[invoice_page_url]'  => $invoice_page_url   ?? "",
			'[invoice_form_url]'  => $invoice_form_url   ?? "",
			
			'[invoice_status]'    => $invoice_status     ?? "",
			'[amount_due]'        => $amount_due         ?? "",
			'[amount_paid]'       => $amount_paid        ?? "",
			'[remaining_balance]' => $remaining_balance  ?? "",
			
			'[due_date]'          => $due_date           ?? "",
			'[remaining_time]'    => $remaining_time     ?? "",
			
			'[first_name]'        => $first_name         ?? "",
			'[last_name]'         => $last_name          ?? "",
			'[full_name]'         => $full_name          ?? "",
			
			'[email]'             => $email              ?? "",
			'[phone_number]'      => $phone_number       ?? "",
			
			'[address]'           => $address            ?? "",
			'[address_2]'         => $address_2          ?? "",
			'[city]'              => $city               ?? "",
			'[state]'             => $state              ?? "",
			'[zip]'               => $zip                ?? "",
			'[country]'           => $country            ?? "",
			
			'[tour_date]'         => $tour_date          ?? "",
			
		);
		
		// Add general merge tags (site_url, etc)
		$general_tags = ah_get_general_merge_tags();
		
		// Merge the merge tag arrays
		$tags = array_merge( $general_tags, $tags );
		
		return $tags;
	}
	
	// Displays merge tags for your most recent invoice
	// https://alpinehikers.com.com/?test_invoice_merge_tags
	// https://alpinehikerdev.wpengine.com/?test_invoice_merge_tags
	public function test_invoice_merge_tags() {
		if ( ! current_user_can( 'administrator' ) ) aa_die( __CLASS__ . '::' . __FUNCTION__ . ' is admin only' );
		
		$user_id = get_current_user_id();
		$invoices = $this->get_user_invoices( $user_id );
		
		if ( !$invoices->have_posts() ) aa_die( 'You must be assigned at least one invoice to view invoice merge tags.' );
		
		$invoice = $invoices->posts[0];
		
		echo '<h2>', $invoice->post_title, '</h2>';
		
		echo '<h3>Owner: ', $this->get_owner_full_name( $invoice->ID ), ' #', $this->get_owner( $invoice->ID ), '</h3>';
		
		echo do_shortcode( '[ah_invoice_merge_tags_preview invoice_id="'. $invoice->ID .'"]' );
		
		exit;
	}
	
	/**
	 * Alias of @see Class_AH_Reminders::setup_reminder_notifications
	 */
	public function setup_reminder_notifications( $invoice_id ) {
		AH_Reminders()->setup_reminder_notifications( $invoice_id );
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
		if ( ! $this->is_valid( $post_id ) ) return false;
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
		if ( ! $this->is_valid( $post_id ) ) return false;
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
		if ( ! $this->is_valid( $post_id ) ) return false;
		
		$status = $this->get_invoice_status( $post_id );
		if ( $status == 'Paid' ) return false;
		
		return true;
	}
	
	/**
	 * Apply a payment amount to an invoice, automatically changing the status to Paid.
	 * If not paid in full, status changes back to Awaiting Payment.
	 *
	 * @param $invoice_id
	 * @param $amount_paid
	 *
	 * @return void
	 */
	public function apply_payment( $invoice_id, $amount_paid ) {
		// Get amount due and amount currently paid
		$total_due = (float) get_field( 'amount_due', $invoice_id, false );
		$total_paid = (float) get_field( 'amount_paid', $invoice_id, false );
		
		// Add the payment to the amount paid
		$total_paid += $amount_paid;
		update_field( 'amount_paid', $total_paid, $invoice_id );
		
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