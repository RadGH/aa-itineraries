<?php

class Class_AH_Admin {
	
	public function __construct() {
		
		// Register ACF settings pages
		add_action( 'admin_menu', array( $this, 'register_menus' ), 20 );
		
		// Register custom image sizes
		add_action( 'after_setup_theme', array( $this, 'register_image_sizes' ) );
		
	}
	
	public function register_menus() {
		if ( function_exists('acf_add_options_page') ) {
			
			// Account Pages -> Settings
			acf_add_options_page(array(
				'page_title' 	=> 'Account Page Settings (ah_account_page)',
				'menu_title' 	=> 'Settings',
				'parent_slug' 	=> 'edit.php?post_type=ah_account_page',
				'post_id'       => 'ah_account_page',
				'slug'          => 'acf-ah-account-page',
				'autoload'      => false,
				'capability'    => 'manage_options',
			));
			
			// Invoices -> Settings
			acf_add_options_page(array(
				'page_title' 	=> 'Invoice Settings (ah_invoices)',
				'menu_title' 	=> 'Settings',
				'parent_slug' 	=> 'edit.php?post_type=ah_invoice',
				'post_id'       => 'ah_invoices',
				'slug'          => 'acf-ah-invoices',
				'autoload'      => false,
				'capability'    => 'manage_options',
			));
			
		}
	}
	
	public function register_image_sizes() {
		add_image_size( 'document-preview', 300, 300, false );
	}
	
}