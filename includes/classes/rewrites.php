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
		
		// Account and Invoice pages
		add_rewrite_rule(
			'(account/invoice)/([^/]+)?(:/([0-9]+))?/?$',
			'index.php?post_type=ah_invoice&ah_invoice=$matches[2]&page=&slug=$matches[1]',
			'top'
		);
		
		// Documents with ability to download
		add_rewrite_rule(
			'(documents)/([^/]+)?(:/([0-9]+))?/download/?$',
			'index.php?post_type=ah_document&ah_document=$matches[2]&page=&slug=$matches[1]&ah_action=download_document',
			'top'
		);
		
		// Smartsheet callback URL
		add_rewrite_rule(
			'(smartsheet)/([^/]+)?(:/([0-9]+))?/?$',
			'index.php?pagename=&page=&ah_action=smartsheet_webhook&ah_webhook=$matches[2]',
			'top'
		);
		
		// Allow hikes, villages, itineraries, and itinerary templates; to "download" a pdf or "preview" a pdf
		// see theme.php -> load_template()
		add_rewrite_rule(
			'hikes/([^/]+)/(download|preview)/?$',
			'index.php?post_type=ah_hike&ah_hike=$matches[1]&ah_action=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'villages/([^/]+)/(download|preview)/?$',
			'index.php?post_type=ah_village&ah_village=$matches[1]&ah_action=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'itineraries/([^/]+)/(download|preview)/?$',
			'index.php?post_type=ah_itinerary&ah_itinerary=$matches[1]&ah_action=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'itinerary-templates/([^/]+)/(download|preview)/?$',
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