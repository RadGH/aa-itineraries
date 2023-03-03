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
		
		add_rewrite_rule(
			'(account/invoice)/([^/]+)?(:/([0-9]+))?/?$',
			'index.php?post_type=ah_invoice&ah_invoice=$matches[2]&page=&slug=$matches[1]',
			'top'
		);
		
		add_rewrite_rule(
			'(documents)/([^/]+)?(:/([0-9]+))?/download/?$',
			'index.php?post_type=ah_document&ah_document=$matches[2]&page=&slug=$matches[1]&ah_action=download_document',
			'top'
		);
		
	}
	
	public function add_query_vars($query_vars) {
		$query_vars[] = 'ah_action';
		return $query_vars;
	}
	
}