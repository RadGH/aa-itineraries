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
	
	return wp_login_form($args);
}
add_shortcode( 'ah_login_form', 'shortcode_ah_login_form' );