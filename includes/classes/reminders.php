<?php

class Class_AH_Reminders {
	
	public $debug_mode = false;
	public $debug_log_items = array();
	
	public $reminder_dates = array(
		'reminder_1' => '-2 weeks',  // 2 weeks early
		'reminder_2' => '-1 week',   // 1 week early
		'reminder_3' => '-1 minute', // same day
		'reminder_4' => '+3 days',   // 3 days late
	);
	
	public function __construct() {
		
		// Send reminder emails manually, the same way it is triggered by cron, but with some debug information too.
		// https://alpinehikerdev.wpengine.com/?manually_send_daily_reminders
		if ( isset($_GET['manually_send_daily_reminders']) ) {
			add_action( 'init', array( $this, 'manually_send_daily_reminders' ) );
		}
	}
	
	public function get_reminder_dates( $sort = false ) {
		$dates = $this->reminder_dates;
		
		// Calculate the offset of each reminder, then sort by the resulting dates
		if ( $sort ) {
			$times = array();
			
			foreach( $dates as $key => $offset ) {
				$times[] = array(
					'time' => strtotime( $offset ),
					'key' => $key,
					'offset' => $offset,
				);
			}
			
			// Sort by calculated time
			uasort( $times, function($a,$b) { return $b['time'] - $a['time']; });
			
			// Recreate array with new order
			$dates = array();
			foreach( $times as $t ) $dates[ $t['key'] ] = $t['offset'];
		}
		
		return $dates;
	}
	
	public function add_debug_message( $message = null, $data = null ) {
		$this->debug_log_items[] = array( $message, $data );
	}
	
	public function add_debug_data( $data = null ) {
		$this->debug_log_items[] = array( null, $data );
	}
	
	public function print_debug_log() {
		if ( ! $this->debug_mode ) return;
		if ( ! $this->debug_log_items ) return;
		
		echo '<div class="ah-debug-log">';
		foreach( $this->debug_log_items as $i => $item ) {
			$message = $item[0];
			$data = $item[1];
			
			if ( $message !== null ) echo '<pre>', $message, '</pre>';
			if ( $data !== null ) echo '<pre>', esc_html(print_r($data, true)), '</pre>';
		}
		echo '</div>';
	}
	
	public function manually_send_daily_reminders() {
		if ( ! current_user_can( 'administrator' ) ) aa_die( 'Must be an admin to access this feature' );
		
		$this->debug_mode = true;
		
		$this->send_daily_reminders();
		
		$this->print_debug_log();
		
		exit;
	}
	
	public function send_daily_reminders() {
		// Only send emails between 8am and 10pm (G = Hours from 0-23)
		if ( current_time('G') < 8 ) return;
		if ( current_time('G') >= 22 ) return;
		
		// Calculate reminder dates based on the invoice due date
		$today_ymd = current_time('Y-m-d');
		$today_ts = strtotime($today_ymd);
		
		// Set up reminder for each date
		foreach( $this->get_reminder_dates(true) as $key => $offset ) {
			
			// "-2 weeks" added to the due date = send email two weeks early
			// $reminder_date_ts = strtotime( $offset, $today_ts );
			// $reminder_date_ymd = date( 'Y-m-d', $reminder_date_ts );
			
			$args = array(
				'post_type' => AH_Plugin()->Invoice->get_post_type(),
				'meta_query' => array(
					array(
						'key' => "{$key}_enabled",
						'value' => '1',
					),
					array(
						'key' => "{$key}_date",
						'value' => $today_ymd, // $reminder_date_ymd
						'compare' => '<=',
						'type' => 'DATE',
					),
				),
			);
			
			$q = new WP_Query( $args );
			
			$this->add_debug_message( '<br><strong>'. $key . ' ('. $offset .'):</strong> Found ' . $q->found_posts . ' invoice(s)' );
			
			if ( $q->have_posts() ) foreach( $q->posts as $post ) {
				$this->send_daily_reminder_email( $post->ID, $key, $offset );
			}
		}
	}
	
	public function send_daily_reminder_email( $invoice_id, $reminder_key, $reminder_offset ) {
		
		$this->add_debug_message('Preparing to send daily reminder for invoice #'. $invoice_id );
		
		$owner_user_id = ah_get_invoice_owner( $invoice_id );
		
		$today_ymd = current_time( 'Y-m-d' );
		$today_ts = strtotime($today_ymd);
		
		$reminder_ymd = get_post_meta( $invoice_id, "{$reminder_key}_date", true );
		$reminder_ts = strtotime($reminder_ymd);
		
		$this->add_debug_data(compact( 'invoice_id', 'reminder_key', 'reminder_offset', 'today_ymd', 'reminder_ymd' ));
		
		// Reminder in the future - do not send yet
		if ( $today_ts > $reminder_ts ) {
			$this->add_debug_message('Error: Attempted to send a reminder email that is scheduled in the future, skipping');
			return;
		}
		
		// Check if already sent a reminder today
		$last_reminder_sent_ymd = get_post_meta( $invoice_id, 'last_reminder_sent_ymd', true );
		if ( $last_reminder_sent_ymd == $today_ymd ) {
			
			if ( $this->debug_mode && get_current_user_id() == $owner_user_id ) {
				$this->add_debug_message('Notice: A different reminder was sent today. For debugging your own invoice, this will be ignored and will send again.');
			}else{
				$this->add_debug_message('Error: A different reminder was sent today. Disabling this reminder.');
				update_post_meta( $invoice_id, "{$reminder_key}_enabled", 0 );
				update_post_meta( $invoice_id, "{$reminder_key}_notes", 'Disabled: A different reminder was sent today' );
				return;
			}
		}
		
		// Get recipient
		$first_name = get_post_meta( $invoice_id, 'first_name', true );
		$last_name = get_post_meta( $invoice_id, 'last_name', true );
		$email = get_post_meta( $invoice_id, 'email', true );
		
		if ( ! $email ) {
			$first_name = ah_get_user_field( $owner_user_id, 'first_name' );
			$last_name = ah_get_user_field( $owner_user_id, 'last_name' );
			$email = ah_get_user_field( $owner_user_id, 'user_email' );
			if ( ! $email ) {
				$this->add_debug_message('Error: Recipient email not set. Disabling this reminder.');
				update_post_meta( $invoice_id, "{$reminder_key}_enabled", 0 );
				update_post_meta( $invoice_id, "{$reminder_key}_notes", 'Disabled: Recipient email not set' );
				return;
			}
		}
		
		// Prepare the email
		$subject = get_field( "{$reminder_key}_subject", 'ah_invoices' );
		$body = get_field( "{$reminder_key}_body", 'ah_invoices' );
		$this->add_debug_data(compact('subject', 'body'));
		
		// Apply merge tags
		$merge_tags = ah_get_invoice_merge_tags( $invoice_id );
		$this->add_debug_data(compact('merge_tags'));
		
		$subject = ah_apply_merge_tags( $subject, $merge_tags );
		$body = ah_apply_merge_tags( $body, $merge_tags );
		$this->add_debug_data(compact('subject', 'body'));
		
		// Email settings
		$to = ($first_name && $last_name) ? "$first_name $last_name <$email>" : $email;
		$from = get_field( "reminder_from", 'ah_invoices' );
		$headers = array( 'Content-Type: text/html' );
		if ( $from ) $headers[] = 'From: ' . $from;
		$this->add_debug_data(compact('to', 'headers'));
		
		// Send email
		$sent_date = current_time( 'm/d/Y g:i:s a' );
		$result = wp_mail( $to, $subject, $body, $headers );
		
		if ( $result ) {
			$this->add_debug_message( 'Email reminder sent successfully' );
			
			// Remember that a reminder was sent today for this invoice
			update_post_meta( $invoice_id, "last_reminder_sent_ymd", $today_ymd );
			update_post_meta( $invoice_id, "last_reminder_sent_data", array($invoice_id, $reminder_key, $reminder_offset) );
			
			// Mark the reminder as sent
			update_post_meta( $invoice_id, "{$reminder_key}_enabled", 0 );
			update_post_meta( $invoice_id, "{$reminder_key}_sent", 1 );
			update_post_meta( $invoice_id, "{$reminder_key}_notes", 'Sent on ' . $sent_date );
			
		}else{
			$this->add_debug_message( 'ERROR: Failed to send email. Disabling this reminder.' );
			update_post_meta( $invoice_id, "{$reminder_key}_enabled", 0 );
			update_post_meta( $invoice_id, "{$reminder_key}_notes", 'Note: Failed to send at ' . $sent_date . '. Will try again.' );
		}
		
		// END send_daily_reminder_email()
	}
	
	public function setup_reminder_notifications( $invoice_id ) {
		$status = ah_get_invoice_status( $invoice_id );
		$balance_due = ah_get_invoice_remaining_balance( $invoice_id );
		
		// Calculate reminder dates based on the invoice due date
		$due_date_ymd = ah_get_invoice_due_date( $invoice_id );
		$due_date_ts = strtotime($due_date_ymd);
		
		$today_ymd = current_time('Y-m-d');
		$today_ts = strtotime($today_ymd);
		
		// A user must be assigned to the invoice
		$owner_user_id = ah_get_invoice_owner( $invoice_id );
		
		// For unattached invoices: Clear all reminders except for those which were sent
		if ( empty($owner_user_id) ) {
			foreach( $this->reminder_dates as $key => $offset ) {
				$is_sent = (string) get_post_meta( $invoice_id, "{$key}_sent", true );
				if ( $is_sent ) continue;
				
				update_post_meta( $invoice_id, "{$key}_enabled", 0 );
				update_post_meta( $invoice_id, "{$key}_sent", 0 );
				update_post_meta( $invoice_id, "{$key}_date", '' );
				update_post_meta( $invoice_id, "{$key}_notes", 'Disabled: No user assigned to invoice' );
			}
			
			return;
		}
		
		// Set up reminder for each date
		foreach( $this->reminder_dates as $key => $offset ) {
			
			// "-2 weeks" added to the due date = send email two weeks early
			$reminder_date_ts = strtotime( $offset, $due_date_ts );
			$reminder_date_ymd = date( 'Y-m-d', $reminder_date_ts );
			
			$is_enabled = (string) get_post_meta( $invoice_id, "{$key}_enabled", true );
			$is_sent = (string) get_post_meta( $invoice_id, "{$key}_sent", true );
			
			$current_date_ymd = (string) get_post_meta( $invoice_id, "{$key}_date", true );
			$current_date_ts = strtotime($current_date_ymd);
			$current_date_ymd = date( 'Y-m-d', $current_date_ts ); // Make date consistent
			
			// Ignore if the date is the same - already calculated
			if ( $current_date_ymd == $reminder_date_ymd ) {
				continue;
			}
			
			// Ignore if sent, mark as disabled
			if ( $is_sent === "1" ) {
				update_post_meta( $invoice_id, "{$key}_enabled", 0 );
				continue;
			}
			
			// Disable if paid or no balance due
			if ( $status == 'Paid' || $balance_due <= 0 ) {
				if ( $is_enabled ) {
					update_post_meta( $invoice_id, "{$key}_enabled", 0 );
					update_post_meta( $invoice_id, "{$key}_notes", 'Reminder disabled: Invoice is paid' );
				}
				continue;
			}
			
			// Enable reminders for the future, otherwise disable it
			if ( $reminder_date_ts >= $today_ts ) {
				update_post_meta( $invoice_id, "{$key}_enabled", 1 );
				update_post_meta( $invoice_id, "{$key}_sent", 0 );
				update_post_meta( $invoice_id, "{$key}_date", $reminder_date_ymd );
				update_post_meta( $invoice_id, "{$key}_notes", '' );
			}else{
				update_post_meta( $invoice_id, "{$key}_enabled", 0 );
				update_post_meta( $invoice_id, "{$key}_sent", 0 );
				update_post_meta( $invoice_id, "{$key}_date", $reminder_date_ymd );
				update_post_meta( $invoice_id, "{$key}_notes", 'Not scheduled: Reminder date has passed' );
			}
			
		}
		// End of setting up reminders
	}
	
}