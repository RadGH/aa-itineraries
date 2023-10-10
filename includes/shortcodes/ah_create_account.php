<?php

function shortcode_ah_create_account( $atts, $content = '', $shortcode_name = 'ah_create_account' ) {
	$atts = shortcode_atts(array(
	), $atts, $shortcode_name);
	
	// Check if the user is logged in
	if ( is_user_logged_in() ) {
		// Offer a link to the account page, or to sign out
		$username = ah_get_user_full_name(get_current_user_id());
		$account_url = site_url( '/account/' );
		$logout_url = wp_logout_url( site_url( '/account/' ) );
		
		$message = '<div class="ah-create-account logged-in">';
		$message .= '<p>You are currently logged in as '. esc_html($username) .'.</p> ';
		$message .= '<p> <a href="'. esc_attr($account_url) .'" class="button button-primary">Go to My Account</a> &nbsp; or &nbsp; <a href="'. esc_attr($logout_url) .'" class="button">Sign out</a></p>';
		$message .= '</div>';
		
		return $message;
	}
	
	// An invitation is required in order to create an account using this form.
	// Invitations can only be created through a field on an itinerary.
	$code = $_GET['ah_invite'] ?? false;
	$itinerary_id = $_GET['itinerary_id'] ?? false;
	
	// Get the invitation
	$invite = AH_Users()->get_invitation_by_code( $itinerary_id, $code );
	
	// Error if invalid invitation
	if ( ! $invite ) {
		$message = '<div class="ah-create-account error">';
		$message .= '<p>We\'re sorry, the link you followed is not valid.</p>';
		$message .= '</div>';
		
		return $message;
	}
	
	// Show a form to create an account
	ob_start();
	?>
	<div class="ah-create-account">
		
		<form action="" method="POST" id="ah_create_account_form">
			
			<div class="ah-field-group first-name half-width">
				<label for="ah_first_name">First Name:</label>
				<input type="text" id="ah_first_name" name="ah[first_name]" value="" required>
			</div>
			
			<div class="ah-field-group last-name half-width">
				<label for="ah_last_name">Last Name:</label>
				<input type="text" id="ah_last_name" name="ah[last_name]" value="" required>
			</div>
			
			<div class="ah-field-group email">
				<label for="ah_email">Email Address:</label>
				<input type="email" id="ah_email" name="ah[email]" value="" required>
			</div>
			
			<div class="ah-field-group password">
				<label for="ah_password">Password:</label>
				<input type="password" id="ah_password" name="ah[password]" value="" required>
			</div>
			
			<div class="ah-field-group password">
				<label for="ah_password_2">Confirm Password:</label>
				<input type="password" id="ah_password_2" name="ah[password_2]" value="" required>
			</div>
			
			<div class="ah-submit">
				<input type="submit" value="Create Account">
				
				<input type="hidden" name="ah[invite_code]" value="<?php echo esc_attr($code); ?>">
				<input type="hidden" name="ah[itinerary_id]" value="<?php echo esc_attr($itinerary_id); ?>">
			</div>
			
		</form>
		
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ah_create_account', 'shortcode_ah_create_account' );
