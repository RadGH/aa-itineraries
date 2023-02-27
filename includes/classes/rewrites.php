<?php

class Class_AH_Rewrites {
	
	public function __construct() {
		// Add rewrite hook and query vars
		// @see https://developer.wordpress.org/reference/functions/add_rewrite_rule/#comment-3503
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		
		// add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}
	
	public function add_rewrite_rules() {
		add_rewrite_rule(
			'(account/invoice)/([^/]+)?(:/([0-9]+))?/?$',
			'index.php?post_type=ah_invoice&ah_invoice=$matches[2]&page=&slug=$matches[1]',
			'top'
		);
		
		/*
		$post_id = get_field( 'account_page_home', 'ah_account_page' );
		$post = get_post( $post_id );
		
		if ( $post ) {
			add_rewrite_rule(
				'account/?$',
				'index.php?post_type=ah_account_page&ah_account_page='. $post->post_name .'&page=&slug=account',
				'top'
			);
		}
		*/
		
		// 'index.php?ah_action=account_home&ah_key=$matches[1]',
	}
	
	/*
	public function add_query_vars($query_vars) {
		$query_vars[] = 'ah_action';
		$query_vars[] = 'ah_key';
		return $query_vars;
	}
	*/
	
}