<?php

/**
 * Remove tours from the itinerary site (these are only used on the live site)
 *
 * @return void
 */
function ah_unregister_tours() {
	unregister_post_type( 'tours' );
}
add_action( 'init', 'ah_unregister_tours', 20 );

/**
 * Redirects to the old site
 */
function ah_redirect_old_site() {
	// https://goalpinehikers.wpengine.com/account/invoice/ah_invoice-6498c51f0e5ab/
	// $current_url = "/account/invoice/ah_invoice-6498c51f0e5ab/"
	
	// https://goalpinehikers.wpengine.com/tours/guided-group/guided-classic-bernese-oberland-traverse/
	// $current_url = "/tours/guided-group/guided-classic-bernese-oberland-traverse/"
	$current_url = $_SERVER['REQUEST_URI'];
	$live_url = 'https://alpinehikers.com' . $current_url;
	
	// Tours
	if ( str_starts_with( '/tours/', $current_url ) ) {
		header('Alpine-Hikers-Redirect: Tours' );
		wp_redirect($live_url);
		exit;
	}
}
add_action( 'template_redirect', 'ah_redirect_old_site' );