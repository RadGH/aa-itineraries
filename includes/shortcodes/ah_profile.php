<?php

function shortcode_ah_profile( $atts, $content = '', $shortcode_name = 'ah_profile' ) {
	$atts = shortcode_atts(array(
	), $atts, $shortcode_name);
	
	ob_start();
	
	ah_profile_display_form();
	
	return ob_get_clean();
}
add_shortcode( 'ah_profile', 'shortcode_ah_profile' );

function ah_profile_display_form() {
	if ( ! is_user_logged_in() ) {
		// If not logged in, show login form instead
		echo do_shortcode( '[ah_login_form]' );
		return;
	}
	
	$user = wp_get_current_user();
	
	$data = ah_profile_get_submitted_data();
	if ( ! $data ) $data = array();
	
	// Get validation errors if data was previously submitted
	$errors = $data ? ah_profile_get_validation_errors( $data ) : false;
	
	// Use submitted data in case of validation errors. Use current profile settings otherwise.
	$first_name       = $data['first_name']       ?? $user->get('first_name');
	$last_name        = $data['last_name']        ?? $user->get('last_name');
	$email            = $data['email']            ?? $user->get('user_email');
	$current_password = $data['current_password'] ?? '';
	$new_password_1   = $data['new_password_1']   ?? '';
	$new_password_2   = $data['new_password_2']   ?? '';
	?>
	<form method="POST" class="ah-profile-form">
		
		<?php
		// Display validation errors (if any)
		if ( $errors ) {
			$html  = '<p>Please correct the following errors:</p>';
			$html .= '<ul><li>'. implode('</li><li>', $errors) .'</li></ul>';
			ah_display_theme_notice( 'error', $html, array(), 'ah-validation-error-summary' );
		}
		
		// Display profile updated message (if updated)
		if ( isset($_GET['ah_profile_updated']) ) {
			$html  = '<p>Your profile has been updated.</p>';
			ah_display_theme_notice( 'success', $html, array(), 'ah-profile-updated-message' );
		}
		?>
		
		<h3>Profile</h3>
		
		<div class="ah-field ah-field--name <?php echo isset($errors['name']) ? 'ah-validation-error' : ''; ?>">
			<label for="ah-first-name" class="ah-label">Your Name:</label>
			<div class="ah-input">
				<input type="text" name="ah[first_name]" value="<?php echo esc_attr($first_name); ?>" placeholder="First Name" required id="ah-first-name">
				<input type="text" name="ah[last_name]" value="<?php echo esc_attr($last_name); ?>" placeholder="Last Name" required>
			</div>
			<?php if ( isset($errors['name']) ) { ?>
			<div class="ah-validation-error-message"><?php echo wpautop($errors['name']); ?></div>
			<?php } ?>
		</div>
		
		<div class="ah-field ah-field--email <?php echo isset($errors['email']) ? 'ah-validation-error' : ''; ?>">
			<label for="ah-email" class="ah-label">Email Address:</label>
			<div class="ah-input">
				<input type="text" name="ah[email]" value="<?php echo esc_attr($email); ?>" required id="ah-email">
			</div>
			<?php if ( isset($errors['email']) ) { ?>
				<div class="ah-validation-error-message"><?php echo wpautop($errors['email']); ?></div>
			<?php } ?>
		</div>
		
		<h3>Update Password (Optional)</h3>
		
		<div class="ah-field ah-field--current-password <?php echo isset($errors['current_password']) ? 'ah-validation-error' : ''; ?>">
			<label for="ah-current-password" class="ah-label">Current Password:</label>
			<div class="ah-input">
				<input type="password" name="ah[current_password]" value="<?php echo esc_attr($current_password); ?>" id="ah-current-password" title="Enter at least 6 characters" minlength="6">
			</div>
			<?php if ( isset($errors['current_password']) ) { ?>
				<div class="ah-validation-error-message"><?php echo wpautop($errors['current_password']); ?></div>
			<?php } ?>
		</div>
		
		<div class="ah-field ah-field--new-password-1 <?php echo isset($errors['new_password_1']) ? 'ah-validation-error' : ''; ?>">
			<label for="ah-new-password-1" class="ah-label">New Password:</label>
			<div class="ah-input">
				<input type="password" name="ah[new_password_1]" value="<?php echo esc_attr($new_password_1); ?>" id="ah-new-password-1" title="Enter at least 6 characters" minlength="6">
			</div>
			<?php if ( isset($errors['new_password_1']) ) { ?>
			<div class="ah-validation-error-message"><?php echo wpautop($errors['new_password_1']); ?></div>
			<?php } ?>
		</div>
		
		<div class="ah-field ah-field--new-password-2 <?php echo isset($errors['new_password_2']) ? 'ah-validation-error' : ''; ?>">
			<label for="ah-new-password-2" class="ah-label">Confirm Password:</label>
			<div class="ah-input">
				<input type="password" name="ah[new_password_2]" value="<?php echo esc_attr($new_password_2); ?>" id="ah-new-password-2" title="Enter at least 6 characters" minlength="6">
			</div>
			<?php if ( isset($errors['new_password_2']) ) { ?>
			<div class="ah-validation-error-message"><?php echo wpautop($errors['new_password_2']); ?></div>
			<?php } ?>
		</div>
		
		<div class="ah-field ah-submit">
			<input type="hidden" name="ah[action]" value="update_profile">
			<input type="hidden" name="ah[nonce]" value="<?php echo wp_create_nonce('update_profile'); ?>">
			
			<input type="submit" value="Save Changes" class="button button-primary">
		</div>
		
	</form>
	
	<script type="text/javascript">
		// Clear the profile updated query arg with js
		(function() {
			if ( window.location.href.indexOf('ah_profile_updated=1') ) {
				let url = window.location.href.replace(/(\?|\&)ah_profile_updated=1/, '');
				window.history.replaceState({}, '', url);
			}
		})();
	</script>
	<?php
}

/**
 * Get the data submitted by the profile form, or false if not submitted.
 *
 * @return array|false
 */
function ah_profile_get_submitted_data() {
	$data = isset($_POST['ah']) ? stripslashes_deep($_POST['ah']) : false;
	if ( ! $data ) return false;
	
	$action = $data['action'] ?? false;
	if ( $action != 'update_profile' ) return false;
	
	$nonce = $data['nonce'] ?? false;
	if ( ! wp_verify_nonce( $nonce, 'update_profile' ) ) {
		wp_die('Session expired, please try again');
	}
	
	// Required fields and their defaults if not provided
	$data = shortcode_atts(array(
		'action'           => null,
		'nonce'            => null,
		'first_name'       => null,
		'last_name'        => null,
		'email'            => null,
		'current_password' => null,
		'new_password_1'   => null,
		'new_password_2'   => null,
	), $data);
	
	return $data;
}

/**
 * Check if provided profile data is valid in order to update the user's profile.
 * Returns an array of errors if invalid. Keys identify the field that caused the error. Values explain the error.
 * If no validation errors are found, returns false (meaning validation was successful).
 *
 * @param $data
 *
 * @return array|false
 */
function ah_profile_get_validation_errors( $data ) {
	$errors = array();
	
	$user = wp_get_current_user();
	
	$first_name       = $data['first_name']       ?? false;
	$last_name        = $data['last_name']        ?? false;
	$email            = $data['email']            ?? false;
	$current_password = $data['current_password'] ?? false;
	$new_password_1   = $data['new_password_1']   ?? false;
	$new_password_2   = $data['new_password_2']   ?? false;
	
	if ( ! $first_name || ! $last_name ) $errors['name'] = 'First and last name are required.';
	if ( ! $email ) $errors['email'] = 'Email address is required.';
	
	if ( $current_password || $new_password_1 || $new_password_2 ) {
		if ( ! $current_password ) {
			$errors['current_password'] = 'Current password is required when providing a new password.';
		}else if ( ! wp_check_password( $current_password, $user->user_pass, $user->ID ) ) {
			$errors['current_password'] = 'Current password does not match the one for your account. If you forgot your password, <a href="'. wp_lostpassword_url(get_permalink()) .'" target="_blank">click here to recover your account</a>.';
		}
		
		if ( ! $new_password_1 ) $errors['new_password_1'] = 'New password is required.';
		if ( ! $new_password_2 ) $errors['new_password_2'] = 'New password confirmation is required.';
		if ( $new_password_1 != $new_password_2 ) $errors['new_password_2'] = 'New password does not match the confirmation password.';
	}
	
	return $errors ?: false;
}

/**
 * When profile form is submitted, update the user's account, then redirect back to the profile form with a success message.
 * 
 * @return void
 */
function ah_profile_form_submission() {
	$data = ah_profile_get_submitted_data();
	if ( ! $data ) return;
	
	$user = wp_get_current_user();
	if ( ! $user ) return;
	
	// Check validation. Validation errors are not displayed here so we can simply return.
	$validation_errors = ah_profile_get_validation_errors( $data );
	if ( ! empty($validation_errors) ) {
		return;
	}
	
	$first_name       = $data['first_name']       ?? false;
	$last_name        = $data['last_name']        ?? false;
	$email            = $data['email']            ?? false;
	$new_password_1   = $data['new_password_1']   ?? false;
	
	$user_data = array(
		'ID' => $user->ID,
		'first_name' => $first_name,
		'last_name' => $last_name,
		'user_email' => $email,
		'display_name' => $first_name . ' ' . $last_name,
	);
	
	// If updating the password
	if ( $new_password_1 ) {
		$user_data['user_pass'] = $new_password_1;
	}
	
	$result = wp_update_user($user_data);
	
	if ( is_wp_error($result) ) {
		wp_die($result);
		exit;
	}
	
	$url = get_permalink();
	$url = add_query_arg(array('ah_profile_updated' => 1), $url);
	wp_redirect( $url );
	exit;
}
add_action( 'template_redirect', 'ah_profile_form_submission' );