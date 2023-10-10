<?php

/*
Todo list:
1. Add ability to re-send invites
2. Handle invite links: https://goalpinehikers.wpengine.com/itinerary/test-example-itinerary/?ah_invite=kq66YaAm
3. Create users
4. Assign created users to any itineraries that they were invited to
5. Remove from invitation when added as a regular user
6. improve sign in page which is currently front page? https://goalpinehikers.wpengine.com/wp-admin/post.php?post=6898&action=edit
*/

class Class_AH_Users {
	
	public function __construct() {
		
		// Modify the message of an ACF message field to include the invitation email and list
		add_filter( 'acf/load_field/key=field_650bdb744de5a', array( $this, 'acf_invite_by_email_field' ) );
		add_filter( 'acf/load_field/key=field_650bdb5e4de59', array( $this, 'acf_invitation_list_field' ) );
		
		// Ajax: refresh the invitation list
		add_action( 'wp_ajax_ah_refresh_invitation_list', array( $this, 'ah_refresh_invitation_list' ) );
		
		// Ajax: add an email to the invitation list
		add_action( 'wp_ajax_ah_add_invitation', array( $this, 'ajax_ah_add_invitation' ) );
		
		// Ajax: (re-)send an invitation to a user
		add_action( 'wp_ajax_ah_send_invitation', array( $this, 'ajax_send_invitation' ) );
		
		// Ajax: remove an invitation from the itinerary
		add_action( 'wp_ajax_ah_remove_invitation', array( $this, 'ajax_remove_invitation' ) );
		
	}
	
	/**
	 * Installs custom user role "Hiker (hiker)".
	 * This is called from the main plugin file during plugin activation.
	 *
	 * @return void
	 */
	public function setup_roles() {
		
		// Define the capabilities for the Hiker role (same as Subscriber)
		$capabilities = array(
			'read' => true,
		);
		
		// Register the Hiker role
		add_role('hiker', 'Hiker', $capabilities);
		
	}
	
	/**
	 * Replace the "Invite by email" field's message with custom html
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	public function acf_invite_by_email_field( $field ) {
		if ( acf_is_screen('acf-field-group') ) return $field; // never modify the field group edit screen
		
		ob_start();
		?>
		
		<p>Add an email address below to send an invitation to create a user account. Once created, the itinerary will be assigned to the user, in addition to any existing users.</p>
		
		<div id="ah_invite_form">
			<div class="loading">Loading&hellip;</div>
		</div>
		<?php
		
		$field['message'] = ob_get_clean();
		
		return $field;
	}
	
	/**
	 * Replace the "Invitations" field's message with custom html
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	public function acf_invitation_list_field( $field ) {
		if ( acf_is_screen('acf-field-group') ) return $field; // never modify the field group edit screen
		
		ob_start();
		?>
		<div id="ah_invite_list">
			<div class="loading">Loading&hellip;</div>
		</div>
		<?php
		
		$field['message'] = ob_get_clean();
		
		return $field;
	}
	
	/**
	 * Get a list of invitations that are already saved to an itinerary
	 *
	 * @param int $itinerary_id
	 *
	 * @return array {
	 *     @type string       $email         email address
	 *     @type string|null  $sent_date     null or date
	 *     @type string       $code          random code to verify the invitation
	 *     @type int|null     $user_id       user id created by this invite
	 *     @type string|null  $created_date  date the user was created
	 * }
	 */
	public function get_invitations( $itinerary_id ) {
		
		$invites = array();
		
		// Get the list of invites
		$stored_invites = get_post_meta( $itinerary_id, 'ah_user_invitations', true );
		
		if ( $stored_invites ) foreach( $stored_invites as $data ) {
			if ( ! isset($data['email']) ) continue;
			
			$invites[] = array(
				'email'        => $data['email'],
				'sent_date'    => $data['sent_date'],
				'code'         => $data['code'],
				'user_id'      => $data['user_id'] ?? null,
				'created_date' => $data['created_date'] ?? null,
			);
		}
		
		return $invites;
		
	}
	
	/**
	 * Get an invitation by email address.
	 *
	 * @param $itinerary_id
	 * @param $code
	 *
	 * @return array|false
	 */
	public function get_invitation_by_email( $itinerary_id, $code ) {
		
		// Get the list of invites
		$invites = $this->get_invitations( $itinerary_id );
		
		// Locate the invite with that code
		foreach( $invites as $data ) {
			if ( $data['email'] == $code ) {
				return $data;
			}
		}
		
		return false;
		
	}
	
	/**
	 * Get an invitation by the code that is generated during creation. This is used for account creation.
	 *
	 * @param $itinerary_id
	 * @param $code
	 *
	 * @return array|false
	 */
	public function get_invitation_by_code( $itinerary_id, $code ) {
		
		// Get the list of invites
		$invites = $this->get_invitations( $itinerary_id );
		
		// Locate the invite with that code
		foreach( $invites as $data ) {
			if ( $data['code'] == $code ) {
				return $data;
			}
		}
		
		return false;
		
	}
	
	/**
	 * Remove an invitation from the itinerary
	 *
	 * @param $itinerary_id
	 * @param $email
	 *
	 * @return bool
	 */
	public function remove_invitation( $itinerary_id, $email ) {
		
		// Get the list of invitations
		$invites = $this->get_invitations( $itinerary_id );
		
		// Remove the invitation with that email
		$found = false;
		foreach( $invites as $i => $data ) {
			if ( $data['email'] == $email ) {
				unset( $invites[$i] );
				$found = true;
			}
		}
		
		// Update the list of invitations
		if ( $found ) {
			$this->update_invitations( $itinerary_id, $invites );
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Update the list of invitations for an itinerary, saved as post meta
	 *
	 * @param $itinerary_id
	 * @param $invitations
	 *
	 * @return void
	 */
	public function update_invitations( $itinerary_id, $invitations ) {
		
		// Delete existing value, which also clears the cached value
		delete_post_meta( $itinerary_id, 'ah_user_invitations' );
		
		// Update the list of invitations
		update_post_meta( $itinerary_id, 'ah_user_invitations', $invitations );
		
	}
	
	/**
	 * Update a single invitation, and save post meta afterwards
	 *
	 * @param int $itinerary_id
	 * @param string $email
	 * @param array $new_data
	 *
	 * @return bool
	 */
	public function update_single_invitation( $itinerary_id, $email, $new_data ) {
		$invites = $this->get_invitations( $itinerary_id );
		
		$updated = false;
		
		foreach( $invites as $i => $data ) {
			if ( $data['email'] == $email ) {
				
				// Merge $new_data with $data
				$invites[$i] = array_merge( $data, $new_data );
				
				$updated = true;
				
			}
		}
		
		if ( $updated ) {
			$this->update_invitations( $itinerary_id, $invites );
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Add a new invitation to the itinerary, sends the invite email, then updates the post meta.
	 *
	 * @param $itinerary_id
	 * @param $email
	 *
	 * @return bool|string  true on success. false if failed o send. string if other error.
	 */
	public function add_invite( $itinerary_id, $email ) {
		
		// Validate the email address
		if ( ! is_email( $email ) ) {
			return 'Invalid email address: "'. esc_html($email) .'"';
		}
		
		// Check if this user already has an account with that email
		$user = get_user_by( 'email', $email );
		if ( $user && ! is_wp_error($user) ) {
			return 'This email address already has an account. Add them to Assigned User(s) instead.';
		}
		
		// Get the list of invites
		$invites = $this->get_invitations( $itinerary_id );
		
		// Check if the user is already invited
		foreach( $invites as $data ) {
			if ( $data['email'] == $email ) {
				return 'This email address has already been invited.';
			}
		}
		
		// Generate a unique code for this invitation
		$code = wp_generate_password( 8, false );
		
		$new_invite = array(
			'email' => $email,
			'sent_date' => null,
			'code' => $code,
			'user_id' => null,
			'created_date' => null,
		);
		
		// Send the invite
		$sent = $this->send_invitation( $itinerary_id, $new_invite );
		
		if ( $sent ) {
			$new_invite['sent_date'] = current_time('Y-m-d H:i:s');
		}
		
		$invites[] = $new_invite;
		
		$this->update_invitations( $itinerary_id, $invites );
		
		return $sent;
	}
	
	/**
	 * Send an invitation by email
	 *
	 * @param int $itinerary_id
	 * @param array $invite
	 *
	 * @return bool
	 */
	public function send_invitation( $itinerary_id, $invite ) {
		
		$email = $invite['email'];
		$code = $invite['code'];
		
		$create_account_id = get_field( 'pages_create_account', 'ah_settings', false );
		$create_account_url = $create_account_id ? get_permalink( $create_account_id ) : site_url('/create-account/');
		
		$invite_url = add_query_arg(array( 'ah_invite' => $code, 'itinerary_id' => $itinerary_id ), $create_account_url );
		
		$itinerary_url = get_permalink( $itinerary_id );
		$itinerary_url = add_query_arg(array( 'ah_invite' => $code, 'itinerary_id' => $itinerary_id ), $itinerary_url );
		
		$title = get_the_title( $itinerary_id );
		
		$button_style = 'background: #aa4a3b; color: #fff; text-decoration: none; font-weight: 700; display: inline-block; padding: 15px 21px; margin: 10px 0; text-transform: uppercase;';
		
		$to = $email;
		
		$subject = 'You\'ve been invited to join the itinerary "'. esc_html($title) .'" on Alpine Hikers';
		$body = '<p>You\'ve been invited to join the itinerary "'. esc_html($title) .'". Click the link below to create your account:</p>';
		$body .= '<p><a href="'. esc_attr( $invite_url ) .'" style="'. $button_style .'">Accept Invitation</a></p>';
		$body .= '<p>Once you have created an account, use the following link to view your itinerary:</p>';
		$body .= '<p><a href="'. esc_attr( $itinerary_url ) .'">'. esc_html($title) .'</a></p>';
		
		return wp_mail( $to, $subject, $body );

	}
	
	/**
	 * Ajax: refresh the invitation list
	 *
	 * @return void
	 */
	public function ah_refresh_invitation_list() {
		
		// Get the itinerary ID
		$itinerary_id = $_POST['itinerary_id'];
		
		// Get the list html
		$html = $this->get_invitation_list_html( $itinerary_id );
		
		// Return a success message with the new list html
		wp_send_json_success( array(
			'html' => $html,
		) );
		
	}
	
	/**
	 * Get the HTML for the invitation list
	 *
	 * @param $itinerary_id
	 *
	 * @return string
	 */
	public function get_invitation_list_html( $itinerary_id ) {
		
		// Get the list of invites
		$invites = $this->get_invitations( $itinerary_id );
		
		if ( $invites ) {
			ob_start();
			
			foreach( $invites as $data ) {
				$email = $data['email'];
				$sent_date = $data['sent_date'];
				// $code = $data['code'];
				
				?>
				<div class="user-invite" data-email="<?php echo esc_attr($email); ?>">
					<div class="col-email">
						<?php echo esc_html( $email ); ?>
					</div>
					<div class="col-sent">
						<?php
						if ( $sent_date ) {
							$diff_seconds = abs( strtotime($sent_date) - current_time('timestamp') );
							
							if ( $diff_seconds < 10 ) {
								echo 'Invite sent!';
							}else{
								$diff = human_time_diff( strtotime($sent_date), current_time('timestamp') );
								echo sprintf(
									'<abbr title="%s">Sent %s ago</abbr>',
									$sent_date,
									$diff
								);
							}
						}else{
							echo '<em>Not sent</em>';
						}
						?>
					</div>
					<div class="col-actions">
						<input type="button" value="Send Invite" class="send-invite button button-secondary">
						<input type="button" value="Remove" class="remove-invite button button-secondary">
					</div>
				</div>
				<?php
			}
			$html = ob_get_clean();
		}else{
			$html = '<em>No invitations have been sent.</em>';
		}
		
		return $html;
	}
	
	/**
	 * Ajax: add an email to the invitation list
	 *
	 * @return void
	 */
	public function ajax_ah_add_invitation() {
		
		// Get the itinerary ID
		$itinerary_id = $_POST['itinerary_id'];
		
		// Get the email addresses
		$email = stripslashes( $_POST['email'] );
		
		// Add the email to the existing list of invitations
		$result = $this->add_invite( $itinerary_id, $email );
		
		// Data to be returned
		$data = array();
		
		// List changes on true or false, but not on string
		if ( $result === true || $result === false ) {
			// Get the updated list html
			$html = $this->get_invitation_list_html( $itinerary_id );
			$data['html'] = $html;
		}
		
		// Add warning if failed to send to the user, although invitation would still be added
		if ( $result === false ) {
			// Invitation failed to send
			$data['message'] = 'Invitation was added, but failed to send to the user.';
		}else if ( is_string($result) ) {
			// There was a different error
			$data['message'] = $result;
		}
		
		// Return a success message with the new list html
		wp_send_json_success($data);
	}
	
	/**
	 * Ajax: (re-)send an invitation to a user
	 *
	 * @return void
	 */
	public function ajax_send_invitation() {
		
		// Get the itinerary ID
		$itinerary_id = $_POST['itinerary_id'];
		
		// Get the email addresses
		$email = stripslashes( $_POST['email'] );
		
		// Locate the invite with that email
		$invites = $this->get_invitations( $itinerary_id );
		
		foreach( $invites as $data ) {
			if ( $data['email'] == $email ) {
				$invite = $data;
				break;
			}
		}
		
		// If invite not found
		if ( ! $invite ) {
			wp_send_json_error( array(
				'message' => 'Invitation not found for "'. esc_html($email) .'"',
			));
			exit;
		}
		
		// Send the invitation
		$sent = $this->send_invitation( $itinerary_id, $data );
		
		if ( ! $sent ) {
			wp_send_json_error( array(
				'message' => 'Failed to send invitation to "'. esc_html($email) .'"',
			));
			exit;
		}
		
		// Update the date sent
		$this->update_single_invitation( $itinerary_id, $email, array( 'sent_date' => current_time('Y-m-d H:i:s') ) );
		
		// Get the updated list html
		$data = array(
			'html' => $this->get_invitation_list_html( $itinerary_id ),
		);
		
		// Return a success message with the new list html
		wp_send_json_success($data);
		
	}
	
	/**
	 * Ajax: remove an invitation from the itinerary
	 */
	public function ajax_remove_invitation() {
		
		// Get the itinerary ID
		$itinerary_id = $_POST['itinerary_id'];
		
		// Get the email address to remove
		$email = stripslashes( $_POST['email'] );
		
		// Remove the invitation
		$this->remove_invitation( $itinerary_id, $email );
		
		// Get the updated list html
		$html = $this->get_invitation_list_html( $itinerary_id );
		$data['html'] = $html;
		
		// Return a success message with the new list html
		wp_send_json_success($data);
		
	}
	
}