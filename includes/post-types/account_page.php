<?php

class Class_Account_Page_Post_Type extends Class_Abstract_Post_Type {
	
	public $post_type = 'ah_account_page';
	public $use_custom_title = false;
	public $use_custom_slug = false;
	
	public function __construct() {
		
		parent::__construct();
		
		// Called by the theme (single-ah_account_page.php), displays the menu
		add_action( 'ah_display_account_menu', array( $this, 'display_account_menu' ) );
		
		// When visiting the account page archive, redirect to the home page specified in the account page settings
		add_action( 'template_redirect', array( $this, 'redirect_archive_to_account_home' ) );
		
		// When visiting an account page which has restricted access enabled, maybe redirect to a different page
		add_action( 'template_redirect', array( $this, 'redirect_restricted_account_pages' ) );
		
		// Register each account menu as a nav menu location
		add_action( 'init', array( $this, 'register_nav_menus' ) );
		
		// Register each account menu as a nav menu location
		add_filter( 'body_class', array( $this, 'add_body_class' ), 30 );
		
	}
	
	public function add_body_class( $classes ) {
		if ( is_singular( $this->get_post_type() ) ) {
			$classes[] = 'account-page';
		}
		
		return $classes;
	}
	
	/**
	 * Customize the args sent to register_post_type.
	 *
	 * @return array
	 */
	public function get_post_type_args() {
		$args = parent::get_post_type_args();
		
		$args['label'] = 'Account Pages';
		
		$args['labels']['name']           = 'Account Pages';
		$args['labels']['singular_name']  = 'Account Page';
		$args['labels']['menu_name']      = 'Account Pages';
		$args['labels']['name_admin_bar'] = 'Account Pages';
		
		$args['labels']['add_new_item'] = 'Add New Account Page';
		$args['labels']['all_items'] = 'All Account Pages';
		$args['labels']['add_new'] = 'Add Account Page';
		$args['labels']['new_item'] = 'New Account Page';
		$args['labels']['edit_item'] = 'Edit Account Page';
		$args['labels']['update_item'] = 'Update Account Page';
		$args['labels']['view_item'] = 'View Account Page';
		
		$args['menu_icon'] = 'dashicons-id-alt';
		$args['menu_position'] = 20;
		
		$args['publicly_queryable'] = true;
		$args['rewrite'] = array( 'slug' => 'account' );
		
		$args['hierarchical'] = true;
		$args['supports'] = array( 'title', 'author', 'revisions', 'page-attributes' );
		
		$args['show_in_nav_menus'] = true;
		
		// Archive page is used as a redirect to the account home page
		$args['has_archive'] = true;
		
		return $args;
	}
	
	public function display_account_menu() {
		$menu = $this->get_active_menu();
		if ( !$menu ) return;
		
		$location_name = $this->get_menu_location_name( $menu['unique_id'] );
		
		if ( $location_name ) {
			if ( has_nav_menu( $location_name ) ) {
				
				echo '<input type="checkbox" class="screen-reader-text" id="ah-mobile-nav-toggle">';
				
				echo '<div class="ah-mobile-account-nav">';
				
				echo '<label id="ah-mobile-nav-label" for="ah-mobile-nav-toggle">My Account</label>';
				
				wp_nav_menu( array(
					'theme_location' => $location_name,
					'menu_class' => 'ah-account-menu',
					'container' => 'nav',
					'container_class' => 'ah-account-menu-nav',
				) );
				
				echo '</div>';
				
			}else{
				aa_die('Invalid account page menu: ' . $location_name, $menu );
			}
		}else{
			aa_die('Invalid account page menu, missing unique ID', $menu );
		}
		
	}
	
	/**
	 * When visiting the account page archive, redirect to the home page specified in the account page settings
	 *
	 * @return void
	 */
	public function redirect_archive_to_account_home() {
		$is_account_page_home = is_post_type_archive( $this->get_post_type() );
		if ( ! $is_account_page_home ) return;
		
		$menu = $this->get_active_menu();
		if ( ! $menu ) return;
	
		$post_id = $menu['home_page'];
		if ( ! $post_id ) return;
		
		wp_redirect( get_permalink( $post_id ) );
		exit;
	}
	
	/**
	 * When visiting an account page which has restricted access enabled, maybe redirect to a different page
	 *
	 * @return void
	 */
	public function redirect_restricted_account_pages() {
		$is_account_page = is_singular( $this->get_post_type() );
		if ( ! $is_account_page ) return;
		
		$post_id = get_the_ID();
		
		// Is checkbox to enable restriction checked?
		$is_restricted = get_field( 'required_enabled', $post_id );
		if ( ! $is_restricted ) return;
		
		// Get conditions from <select> in a repeater
		$conditions = get_field( 'required_conditions', $post_id ); // array( array('condition' => 'Logged In'), ... )
		if ( !$conditions ) $conditions = array();
		
		// Get comparison type from a select
		$comparison = get_field( 'required_comparison', $post_id ); // all, one, none
		if ( !$comparison ) $comparison = 'any';
		
		// Evaluate each selected condition
		$all_condition_met = true;
		$any_condition_met = false;
		
		foreach( $conditions as $c ) {
			if ( $this->is_condition_met( $c['condition'] ) ) {
				$any_condition_met = true;
			}else{
				$all_condition_met = false;
			}
		}
		
		// Check if the conditions are met. If the user meeta the criteria they are NOT redirected
		$perform_redirect = true;
		if ( $comparison == 'one'  && $any_condition_met ) $perform_redirect = false; // Require one or more
		if ( $comparison == 'all'  && $all_condition_met ) $perform_redirect = false; // Require all
		if ( $comparison == 'none' && ! $any_condition_met ) $perform_redirect = false; // Require none
		
		// Do not redirect if the user matched the requirements
		if ( ! $perform_redirect ) return;
		
		// If user did not meet requirements, redirect to target page (or die if not set)
		$post_id = get_field( 'required_redirect_page', $post_id );
		
		if ( $post_id ) {
			wp_redirect( get_permalink( $post_id ) );
		}else{
			aa_die( 'Access to this page is restricted' );
		}
		
		exit;
	}
	
	/**
	 * Gets a menu by comparing the conditions from the account menu settings page.
	 *
	 * @return array|false
	 */
	public function get_active_menu() {
		// Get all menus
		$menus = get_field( 'menus', 'ah_account_page' );
		if ( !$menus ) return false;
		
		// Loop through each menu, checking the condition. Return the first menu matching that condition.
		foreach( $menus as $menu ) {
			$condition = $menu['condition']; // Logged In, Logged Out
			
			if ( $this->is_condition_met( $condition ) ) return $menu;
		}
		
		return false;
	}
	
	/**
	 * Checks if a certain condition is met by the current user.
	 * Used for field groups "Account Page Conditions" and "Account Page: Menus"
	 *
	 * Conditions available:
	 *      Logged In      - User must be logged in
	 *      Logged Out     - User must NOT be logged in
	 *
	 * @param $condition
	 *
	 * @return bool
	 */
	public function is_condition_met( $condition ) {
		$user_id = is_user_logged_in() ? get_current_user_id() : false;
		
		if ( $condition === 'Logged In' && $user_id ) return true;
		if ( $condition === 'Logged Out' && empty($user_id) ) return true;
		
		return false;
	}
	
	/**
	 * Formats a menu's location name based on the menu's unique_id field.
	 *
	 * @param $unique_id
	 *
	 * @return string
	 */
	public function get_menu_location_name( $unique_id ) {
		$unique_id = strtolower($unique_id);
		$unique_id = sanitize_title_with_dashes($unique_id);
		
		// Add prefix
		$unique_id = 'account_' . $unique_id;
		
		return $unique_id;
	}
	
	public function register_nav_menus() {
		// Get all menus
		$menus = get_field( 'menus', 'ah_account_page' );
		if ( !$menus ) return;
		
		// Register a menu location for each one
		
		foreach( $menus as $menu ) {
			$menu_name = (string) $menu['menu_name'];
			$unique_id = (string) $menu['unique_id'];
			// $condition = (string) $menu['condition'];
			// $home_page = (int) $menu['home_page'];
			
			$location_name = $this->get_menu_location_name( $unique_id );
			$description = 'Account: ' . esc_html($menu_name);
			
			register_nav_menu( $location_name, $description );
		}
		/*
$locations      = array();
$menu_locations = array(
"primary" => 42,
"mobile" => 40,
"mobile-secondary" => 41,
);
		 */
		
	}
	
}