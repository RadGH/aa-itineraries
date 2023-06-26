<?php

class Class_AH_Rewrites {
	
	public function __construct() {
		// Add rewrite hook and query vars
		// @see https://developer.wordpress.org/reference/functions/add_rewrite_rule/#comment-3503
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		
		// Add custom query vars
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}
	
	public function add_rewrite_rules() {
		
		// Documents with ability to download
		add_rewrite_rule(
			'(document)/([^/]+)?(:/([0-9]+))?/download/?$',
			'index.php?post_type=ah_document&ah_document=$matches[2]&page=&slug=$matches[1]&ah_action=download_document',
			'top'
		);
		
		// Smartsheet webhook callback URL
		add_rewrite_rule(
			'(smartsheet)/([^/]+)?(:/([0-9]+))?/?$',
			'index.php?pagename=&page=&ah_action=smartsheet_webhook&ah_webhook=$matches[2]',
			'top'
		);
		
		// Invoices (this is the default slug for the post type, but needed higher priority)
		add_rewrite_rule(
			'(account/invoice)/([^/]+)?/?$',
			'index.php?post_type=ah_invoice&ah_invoice=$matches[2]&page=&slug=$matches[1]',
			'top'
		);
		
		// Allow hikes, villages, itineraries, and itinerary templates; to "download" a pdf or "preview" a pdf
		// see theme.php -> load_template()
		add_rewrite_rule(
			'hike/([^/]+)/(download|preview)/?$',
			'index.php?post_type=ah_hike&ah_hike=$matches[1]&ah_action=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'village/([^/]+)/(download|preview)/?$',
			'index.php?post_type=ah_village&ah_village=$matches[1]&ah_action=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'itinerary/([^/]+)/(download|preview)/?$',
			'index.php?post_type=ah_itinerary&ah_itinerary=$matches[1]&ah_action=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'itinerary-template/([^/]+)/(download|preview)/?$',
			'index.php?post_type=ah_itinerary_tpl&ah_itinerary=$matches[1]&ah_action=$matches[2]',
			'top'
		);
		
	}
	
	public function add_query_vars($query_vars) {
		$query_vars[] = 'ah_action';
		$query_vars[] = 'ah_webhook';
		return $query_vars;
	}
	
}