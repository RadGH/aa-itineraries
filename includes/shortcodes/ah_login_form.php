<?php

function shortcode_ah_login_form( $atts, $content = '', $shortcode_name = 'ah_login_form' ) {
	$atts = shortcode_atts(array(
	), $atts, $shortcode_name);
	
	ob_start();
	
	$args = array(
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
	
	wp_login_form($args);
	
	return ob_get_clean();
}
add_shortcode( 'ah_login_form', 'shortcode_ah_login_form' );