<?php

class Class_AH_Smartsheet_Webhooks  {
	
	public function __construct() {
	
		add_action( 'template_redirect', array( $this, 'capture_webhook_callback' ), 5 );
		
		// View saved webhooks
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_webhooks_view_all
		if ( isset($_GET['ah_smartsheet_webhooks_view_all']) ) add_action( 'init', array( $this, 'ah_smartsheet_webhooks_view_all' ) );
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
		
		// Allow testing with a URL that does not have the header
		if ( isset($_GET['ah_challenge']) ) $challenge = stripslashes($_GET['ah_challenge']);
		
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
		}
		
		AH_Admin()->add_notice( 'info', 'A webhook was not handled properly. The header "Smartsheet-Hook-Challenge" was not defined.', $debug_data, 'webhook_not_handled' );
		
		echo 1;
		exit;
		
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
	
}