<?php

class Class_AH_Smartsheet_Webhooks  {
	
	public function __construct() {
	
		
		// Process to generate invoice webhook, for smartsheet support ticket
		//
		// View saved webhooks
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_webhooks_view_all
		//
		// Delete "invoice" webhook
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_delete_webhook&webhook_action=invoice
		//
		// Create "invoice" webhook (not enabled yet)
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_add_webhook&sheet_id=7609265092355972&webhook_action=invoice&scope=sheet&title=Invoice
		//
		// Enable "invoice" webhook
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_toggle_webhook&webhook_action=invoice&enabled=1
		//
		// Once enabled, a callback is sent that is handled by smartsheet-webhooks.php -> capture_webhook_callback()
		
		// Callback to verify webhook, triggered by Smartsheet
		// Test URL (must provide header):
		add_action( 'template_redirect', array( $this, 'capture_webhook_callback' ), 5 );
		
		// Test a webhook callback by querying the callback with HTTP API
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_webhooks_test_callback&challenge=a7dd660b-d1db-48d2-af49-6acbc7046a82
		if ( isset($_GET['ah_smartsheet_webhooks_test_callback']) ) {
			add_action( 'init', array( $this, 'ah_smartsheet_webhooks_test_callback' ) );
		}
		
		
		// View saved webhooks
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_webhooks_view_all
		if ( isset($_GET['ah_smartsheet_webhooks_view_all']) ) {
			add_action( 'init', array( $this, 'ah_smartsheet_webhooks_view_all' ) );
		}
	}
	
	/**
	 * Save a webhook to the website. Overwrites any existing action. Does not affect Smartsheet.
	 *
	 * @param $action
	 * @param $webhook_id
	 * @param $secret
	 * @param $object_id
	 * @param $scope
	 * @param $title
	 *
	 * @return void
	 */
	public function add_saved_webhook( $action, $webhook_id, $secret, $object_id, $scope, $title ) {
		$webhooks = $this->get_all_saved_webhooks();
		
		$webhooks[ $webhook_id ] = array(
			'action'     => $action,
			'webhook_id' => $webhook_id,
			'secret'     => $secret,
			'object_id'  => $object_id,
			'scope'      => $scope,
			'title'      => $title,
		);
		
		update_option( 'ah_webhooks', $webhooks, false );
	}
	
	/**
	 * Delete a saved webhook from the website. Does not affect Smartsheet.
	 *
	 * @param $webhook_id
	 *
	 * @return void
	 */
	public function delete_saved_webhook( $webhook_id ) {
		$webhooks = $this->get_all_saved_webhooks();
		
		if ( isset($webhooks[ $webhook_id ]) ) {
			unset($webhooks[ $webhook_id ]);
			update_option( 'ah_webhooks', $webhooks, false );
		}
	}
	
	/**
	 * Delete a saved webhook from the website. Does not affect Smartsheet.
	 *
	 * @param $action
	 *
	 * @return void
	 */
	public function delete_saved_webhook_from_action( $action ) {
		$webhooks = $this->get_all_saved_webhooks();
		$saved_webhook = $this->get_saved_webhook_from_action( $action );
		
		if ( $saved_webhook && isset($webhooks[ $saved_webhook['webhook_id'] ]) ) {
			unset($webhooks[ $saved_webhook['webhook_id'] ]);
			update_option( 'ah_webhooks', $webhooks, false );
		}
	}
	
	/**
	 * Get all saved webhooks from the website. Does not query from Smartsheet.
	 *
	 * @return array
	 */
	public function get_all_saved_webhooks() {
		return $this->get_saved_webhook('all');
	}
	
	/**
	 * Get a single saved webhook (or all webhooks if $action is 'all'). Does not query from Smartsheet.
	 *
	 * @param $webhook_id
	 *
	 * @return array|false
	 */
	public function get_saved_webhook( $webhook_id ) {
		$webhooks = get_option( 'ah_webhooks' );
		if ( !$webhooks ) $webhooks = array();
		
		if ( $webhook_id === 'all' ) {
			// All webhooks
			return $webhooks;
		}else{
			// One webhook
			return $webhooks[$webhook_id] ?? false;
		}
	}
	
	/**
	 * Gets a saved webhook based on the action parameter
	 *
	 * @param $action
	 *
	 * @return false|array
	 */
	public function get_saved_webhook_from_action( $action ) {
		$webhooks = $this->get_all_saved_webhooks();
		
		if ( $webhooks ) foreach( $webhooks as $w ) {
			if ( $w['action'] == $action ) {
				return $this->get_saved_webhook( $w['webhook_id'] );
			}
		}
		
		return false;
	}
	
	/**
	 * When a webhook is triggered, this handles the callback.
	 *
	 * @return void
	 */
	public function capture_webhook_callback() {
		
		// rewrite: ah_action=smartsheet_webhook&ah_webhook=$matches[2]
		// url:     /smartsheet/SOMETHING/
		// get_query_var( 'ah_action' ) = 'smartsheet_webhook'
		// get_query_var( 'ah_webhook' ) = 'SOMETHING'
		if ( get_query_var( 'ah_action' ) != 'smartsheet_webhook' ) return;
		
		// Get the webhook that was triggered
		$webhook_action = get_query_var( 'ah_webhook' );
		$saved_webhook = $this->get_saved_webhook_from_action( $webhook_action );
		
		// Get the challenge header
		$headers = getallheaders();
		$challenge = $headers['Smartsheet-Hook-Challenge'] ?? false; // "8467a664-e08d-4f3b-b493-85fa13060622"
		
		// If using Chrome with custom header, it is sent as lower case for some reason. Try lower case.
		if ( ! $challenge ) $challenge = $headers['smartsheet-hook-challenge'] ?? false;
		
		// @debugging
		$debug_data = array(
			'challenge' => $challenge,
			'webhook_action' => $webhook_action,
			'saved_webhook' => $saved_webhook,
			'headers' => $headers,
			'url' => $_SERVER['REQUEST_URI'],
			'GET' => $_GET,
			'POST' => $_POST,
			'SERVER' => $_SERVER,
		);
		
		if ( $challenge ) {
			// Verifying a webhook
			// A webhook must be re-verified every 100 callbacks
			// https://smartsheet.redoc.ly/tag/webhooksDescription#section/Creating-a-Webhook/Webhook-Verification
			
			// @debugging
			AH_Admin()->add_notice( 'info', 'A webhook verification was sent', $debug_data, 'webhook_verification_' . $webhook_action );
			
			// Respond with the challenge code using a custom header and also as the message body
			ob_clean();
			header( 'HTTP/1.1 200 OK', true, 200 );
			header( 'Smartsheet-Hook-Challenge: ' . $challenge );
			echo $challenge;
			// echo json_encode( array('smartSheetHookResponse' => $challenge) );
			exit;
		}else{
			echo '<p><strong>Error: The header "Smartsheet-Hook-Challenge" was not defined.</strong></p>';
			echo '<p>To add custom headers in Chrome (for testing):</p>';
			echo '<br><img style="max-width: 1000px;" src="https://s3.us-west-2.amazonaws.com/elasticbeanstalk-us-west-2-868470985522/ShareX/2023/05/chrome_2023-05-19_14-16-38.png">';
			echo '<br><img style="max-width: 1000px;" src="https://s3.us-west-2.amazonaws.com/elasticbeanstalk-us-west-2-868470985522/ShareX/2023/05/chrome_2023-05-19_14-18-43.png">';
			echo '<br><p>Headers:</p>';
			echo '<pre>';
			var_dump(getallheaders());
			echo '</pre>';
			
			AH_Admin()->add_notice( 'info', 'A webhook was not handled properly. The header "Smartsheet-Hook-Challenge" was not defined.', $debug_data, 'webhook_not_handled' );
			exit;
		}
		
	}
	
	
	// View saved webhooks
	// https://alpinehikerdev.wpengine.com/?ah_smartsheet_webhooks_view_all
	public function ah_smartsheet_webhooks_view_all() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		// To delete webhooks see smartsheet.php
		$all_webhooks = $this->get_all_saved_webhooks();
		
		pre_dump($all_webhooks);
		
		exit;
	}
	
	// Test a webhook callback by querying the callback with HTTP API
	// https://alpinehikerdev.wpengine.com/?ah_smartsheet_webhooks_test_callback&challenge=a7dd660b-d1db-48d2-af49-6acbc7046a82
	public function ah_smartsheet_webhooks_test_callback() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		$url = site_url('/smartsheet/invoice/');
		$challenge = $_GET['challenge'] ?? false;
		
		if ( ! $challenge ) {
			echo 'Add the callback challenge to the URL and try again (challenge does not need to be correct since this does not perform a smartsheet api request).';
			exit;
		}
		
		$args = array(
			'headers' => array(
				'Smartsheet-Hook-Challenge' => stripslashes($challenge),
			),
		);
		
		$response = wp_remote_post( $url, $args );
		
		$in = array(
			'url' => $url,
			'headers' => print_r( (array) $args['headers'], true ),
			'body' => '',
		);
		
		$out = array(
			'headers'      => print_r( $response['headers']->getAll(), true ),
			'body'         => print_r( $response['body'], true ),
			'http_code'    => $response['response']['code'],
			'http_message' => $response['response']['message'],
		);
		
		echo '<p><strong>HTTP Request to the webhook callback URL:</strong></p>';
		echo '<table><tbody>';
		
		foreach( $in as $key => $value ) {
			echo '<tr><td style="width: 120px; vertical-align: top;">', esc_html($key), '</td>';
			echo '<td><pre>', esc_html($value), '</pre></td></tr>';
		}
		
		echo '</tbody></table>';
		
		echo '<p><strong>Response from that request:</strong></p>';
		
		echo '<table><tbody>';
		
		foreach( $out as $key => $value ) {
			echo '<tr><td style="width: 120px; vertical-align: top;">', esc_html($key), '</td>';
			echo '<td><pre>', esc_html($value), '</pre></td></tr>';
		}
		
		echo '</tbody></table>';
		
		
		exit;
	}
	
}