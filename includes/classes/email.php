<?php

class Class_AH_Email {
	
	public function __construct() {
		
		add_filter( 'wp_mail', array( $this, 'custom_email_headers' ), 20 );
		
		// Send or preview a test email
		if ( isset($_GET['ah_test_email']) ) {
			add_action( 'admin_init', array( $this, 'handle_test_email' ) );
		}
		
	}
	
	public function handle_test_email() {
		$send_email = $_GET['ah_test_email'] === 'send'; // if false, preview only
		
		$user = wp_get_current_user();
		
		$to = $user->user_email;
		$subject = 'Test email from ' . get_bloginfo( 'name' );
		$message = '<p>This is a test email from ' . get_bloginfo( 'name' ) . '.</p><p>If you received this email, it means that the email settings are working correctly.</p>';
		
		// This filter allows us to preview the email instead of sending it from within wp_mail()
		if ( ! $send_email ) {
			add_filter( 'ah_preview_wp_mail', '__return_true' );
		}
		
		// Send email
		wp_mail( $to, $subject, $message );
		
		$message = '<p>Test email sent to <strong>' . $to . '</strong></p>';
		$message .= '<p><a href="admin.php?page=acf-ah-email-settings">&larr; Back to Email Settings</a></p>';
		wp_die('Test email sent to ' . $to, 'Test Email Sent', array( 'response' => 200 ));
	}
	
	/**
	 * Customizes outgoing emails if branding is enabled.
	 *
	 * @param array $args {
	 *     @type string|string[] $to          Array or comma-separated list of email addresses to send message.
	 *     @type string          $subject     Email subject.
	 *     @type string          $message     Message contents.
	 *     @type string|string[] $headers     Additional headers.
	 *     @type string|string[] $attachments Paths to files to attach.
	 * }
	 *
	 * @return array
	 */
	public function custom_email_headers( $args ) {
		
		// Optionally apply branding to the email from the email settings page
		if ( get_field( 'enable_email_branding', 'ah_emails' ) ) {
			$args['message'] = $this->apply_email_branding( $args['message'] );
		}
		
		// Headers should be an array
		if ( ! is_array($args['headers']) ) {
			$args['headers'] = $args['headers'] ? (array) $args['headers'] : array();
		}
		
		// Send an HTMl email
		$args['headers'][] = 'Content-type: text/html; charset=utf-8';
		
		// Allow previewing the email with this filter
		if ( apply_filters( 'ah_preview_wp_mail', false ) ) {
			$to_list = is_array( $args['to'] ) ? implode( ', ', $args['to'] ) : $args['to'];
			
			ob_start();
			echo '<p><a href="admin.php?page=acf-ah-email-settings">&larr; Back to Email Settings</a></p>';
			echo '<h1>Previewing Email</h1>';
			echo '<p><strong>To:</strong> ' . esc_html($to_list) . '</p>';
			echo '<p><strong>Subject:</strong> ' . esc_html($args['subject']) . '</p>';
			echo '<p><strong>Headers:</strong></p>';
			echo '<ul><li>' . implode( '</li><li>', $args['headers']) . '</li></ul>';
			echo '<p><strong>Message:</strong></p>';
			echo $args['message'];
			$content = ob_get_clean();
			wp_die( $content, 'Email Preview', array( 'response' => 200 ) );
			exit;
		}
		
		return $args;
	}
	
	public function apply_email_branding( $message ) {
		// 1. Add user-defined header and footer, from email settings page
		$header = get_field( 'custom_email_header', 'ah_emails' );
		$footer = get_field( 'custom_email_footer', 'ah_emails' );
		
		if ( $header ) $message = wpautop($header) . $message;
		if ( $footer ) $message = $message . wpautop($footer);
		
		// 2. Add branded header and footer using site logo
		$logo_id =  (int) get_field( 'logo', 'ah_settings' );
		
		$header = '<div style="background: #204f66; padding: 20px;">';
		$header.= '<div style="background: #ffffff; padding: 20px; margin: 0 auto; max-width: 700px; border-radius: 3px;">';
		
		if ( $logo_id ) {
			$url = wp_get_attachment_image_src( $logo_id, 'large' );
			$size = ah_fit_image_size( $url[1], $url[2], 250 );
			$header .= '<p style="margin-bottom: 40px;"><a href="'. site_url('/account/') .'" target="_blank" style="display: inline-block">';
			$header .= sprintf(
				'<img src="%s" alt="%s" width="%s" height="%s" style="max-width: 100%%; height: auto;" />',
				esc_attr($url[0]),
				esc_attr(get_bloginfo( 'name' )),
				esc_attr($size[0]),
				esc_attr($size[1])
			);
			$header .= '</a></p>';
		}

		
		$footer = '</div>';
		$footer.= '</div>';
		
		if ( $header ) $message = $header . $message;
		if ( $footer ) $message = $message . '<br>' . $footer;
		
		return $message;
	}

	
	
}