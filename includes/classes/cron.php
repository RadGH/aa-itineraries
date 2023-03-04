<?php

class Class_AH_Cron {
	
	public function __construct() {
		
		// Register cron hook when if it is disabled
		add_action( 'shutdown', array( $this, 'register_cron' ) );
		
		// Add a 5 minute cron schedule
		add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );
		
	}
	
	// Schedule a wp_cron event
	public function register_cron() {
		if ( !wp_next_scheduled ( 'ah_cron/5_minute' ) ) {
			wp_schedule_event( time(), 'every_five_minutes', 'ah_cron/5_minute' );
		}
	}
	
	// Clear scheduled cron events
	public function clear_cron() {
		// cron.php (this file)
		wp_clear_scheduled_hook( 'ah_cron/5_minute' );
		
		// do not re-register at shutdown
		remove_action( 'shutdown', array( $this, 'register_cron' ) );
	}
	
	// Add a custom cron event schedule
	public function add_custom_schedules( $schedules ) {
		$schedules[ 'every_five_minutes' ] = array(
			'interval' => 300, // 5 minutes, as seconds
			'display' => 'Every 5 Minutes',
		);
		
		return $schedules;
	}
}