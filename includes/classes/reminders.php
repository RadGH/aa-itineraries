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
	
	public function debug_log( $message, $data = null ) {
		$this->debug_log_items[] = array( $message, $data );
	}
	
	public function print_debug_log() {
		if ( ! $this->debug_mode ) return;
		if ( ! $this->debug_log_items ) return;
		
		echo '<div class="ah-debug-log">';
		foreach( $this->debug_log_items as $i => $item ) {
			$message = $item[0];
			$data = $item[1];
			
			echo '<div class="ah-debug-item">';
			echo $message;
			if ( $data !== null ) pre_dump($data);
			echo '</div>';
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
		foreach( $this->reminder_dates as $key => $offset ) {
			
			// "-2 weeks" added to the due date = send email two weeks early
			$reminder_date_ts = strtotime( $offset, $today_ts );
			$reminder_date_ymd = date( 'Y-m-d', $reminder_date_ts );
			
			$args = array(
				'post_type' => AH_Plugin()->Invoice->get_post_type(),
				'meta_query' => array(
					array(
						'key' => "{$key}_enabled",
						'value' => '1',
					),
					array(
						'key' => "{$key}_date",
						'value' => $reminder_date_ymd,
						'compare' => '<=',
						'type' => 'DATE',
					),
				),
			);
			
			$q = new WP_Query( $args );
			
			$this->debug_log( '<strong>'. $key . ' ('. $offset .'):</strong> Found ' . $q->found_posts . ' invoice(s)' );
			
			if ( $q->have_posts() ) foreach( $q->posts as $post ) {
				$this->send_daily_reminder_email( $post->ID, $key, $offset );
			}
		}
	}
	
	public function send_daily_reminder_email( $invoice_id, $reminder_key, $reminder_offset ) {
		
		$sent_date = current_time( 'm/d/Y g:i:s a' );
		/*
		update_post_meta( $invoice_id, "{$reminder_key}_enabled", 0 );
		update_post_meta( $invoice_id, "{$reminder_key}_sent", 1 );
		update_post_meta( $invoice_id, "{$reminder_key}_date", '' );
		update_post_meta( $invoice_id, "{$reminder_key}_notes", 'Sent on ' . $sent_date );
		*/
		
		$this->debug_log("<pre>
update_post_meta( $invoice_id, '{$reminder_key}_enabled', 0 );
update_post_meta( $invoice_id, '{$reminder_key}_sent' 1 );
update_post_meta( $invoice_id, '{$reminder_key}_date', '' );
update_post_meta( $invoice_id, '{$reminder_key}_notes', 'Sent on ' . $sent_date );
</pre>", array(compact('invoice_id', 'reminder_key', 'reminder_offset', 'sent_date')));
	
	}
	
	public function setup_reminder_notifications( $invoice_id ) {
		$status = ah_get_invoice_status( $invoice_id );
		$balance_due = ah_get_invoice_remaining_balance( $invoice_id );
		
		// Calculate reminder dates based on the invoice due date
		$due_date_ymd = ah_get_invoice_due_date( $invoice_id );
		$due_date_ts = strtotime($due_date_ymd);
		
		$today_ymd = current_time('Y-m-d');
		$today_ts = strtotime($today_ymd);
		
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