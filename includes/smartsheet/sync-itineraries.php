<?php

class Class_AH_Smartsheet_Sync_Itineraries {
	
	public function __construct() {
		
		// Register ACF settings pages
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 25 );
		
	}
	
	public function register_admin_menus() {
		/*
		if ( function_exists('acf_add_options_page') ) {
			// Smartsheet Settings -> Sync Villages and Hotels
			// NOTE: Must be defined by ACF first, then override with a WP submenu page
			acf_add_options_sub_page( array(
				'parent_slug' => 'acf-ah-settings-parent',
				'page_title'  => 'Sync Villages and Hotels',
				'menu_title'  => 'Sync Villages and Hotels',
				'capability' => 'manage_options',
				'menu_slug'   => 'ah-smartsheet-villages-and-hotels',
			) );
			add_submenu_page(
				null,
				'Sync Villages and Hotels',
				'Sync Villages and Hotels',
				'manage_options',
				'ah-smartsheet-villages-and-hotels',
				array( $this, 'display_admin_page' )
			);
			
		}
		*/
	}
	
}