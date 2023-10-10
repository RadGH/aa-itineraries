<?php

function shortcode_ah_login_form( $atts, $content = '', $shortcode_name = 'ah_login_form' ) {
	$atts = shortcode_atts(array(
	), $atts, $shortcode_name);
	
	
	$args = array(
		'echo' => false,
		'form_id' => 'ah-login-form',
		'label_username' => 'Email (Username) *',
		'label_password' => 'Password *',
		'label_log_in' => 'Submit',
		'remember' => true,
		'redirect' => '/account/',
	);
	
	if ( isset($_GET['redirect_to']) ) {
		$args['redirect'] = stripslashes($_GET['redirect_to']);
	}
	
	if ( is_user_logged_in() ) {
		$url = $args['redirect'] ?: site_url( '/account/' );
		wp_redirect( $url );
		exit;
	}
	
	$html = '';
	
	// Check if the user is trying to sign in from an invitation link
	if ( isset($_GET['ah_invited_user']) ) {
		$html .= '<div class="notice"><p>You have already created your account. Please sign in below to view your itinerary.</div>';
	}
	
	$html .= wp_login_form($args);
	
	return $html;
}
add_shortcode( 'ah_login_form', 'shortcode_ah_login_form' );