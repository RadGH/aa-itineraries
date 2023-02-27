<?php

class RS_API {
	
	public $endpoint = '';
	// public $account_sid = null;
	// public $auth_token = null;
	// public $phone_number = null;
	// public $debug_mode = true;
	
	private $auth_header = '';
	private $debug_mode = '';
	
	private $response = null;
	
	public function __construct() {
	
	}
	
	/**
	 * Enable or disable debug mode
	 *
	 * @param $enabled
	 *
	 * @return void
	 */
	public function set_debug_mode( $enabled ) {
		$this->debug_mode = (bool) $enabled;
	}
	
	/**
	 * Sets the authorization header to be used on subsequent requests.
	 * Should not include the "Authorization:" prefix.
	 *
	 * @param $header
	 *
	 * @return void
	 */
	public function set_authorization_header( $header ) {
		if ( str_starts_with( $header, 'Authorization: ' ) ) {
			$header = str_replace( 'Authorization: ', '', $header );
		}
		
		$this->auth_header = $header;
	}
	
	/**
	 * Return basic authentication header
	 * Usage (for args in wp_remote_get):
	 *      $args['headers']['Authorization'] = $this->get_authorization_header();
	 * @return string
	 */
	public function get_authorization_header() {
		// return 'Basic ' . base64_encode( $this->account_sid . ':' . $this->auth_token );
		return $this->auth_header ?: false;
		
	}
	
	/**
	 * Send a text
	 * API endpoint: /Accounts/{AccountSid}/Messages.json
	 * @see https://www.twilio.com/docs/sms/api/message-resource#create-a-message-resource
	 *
	 * @param $to
	 * @param $message
	 *
	 * @return array|WP_Error
	 */
	/*
	public function send_sms( $to, $message ) {
		
		if ( !$this->settings_valid() ) {
			return new WP_Error( 'settings_invalid', 'The Twilio credentials are missing or invalid. The PIW SMS API is disabled.' );
		}
		
		$url = $this->endpoint . "/Accounts/{$this->account_sid}/Messages.json";
		
		$args = array(
			'headers' => array(
				'Authorization' => $this->get_authorization_header(),
			),
			'body'    => array(
				'From' => $this->phone_number,
				'To'   => $to,
				'Body' => $message,
			),
		);
		
		$result = wp_remote_post( $url, $args );
		
		return $result;
	}
	*/
	
	/**
	 * Performs an API request. Returns an array including: response_body, response_code, response_message
	 *
	 * If "response_code" is null, nothing happened.
	 * If "response_code" is -1, error occurred but the API request was not sent
	 * If "response_code" is -2, error occurred during the API request
	 * If "response_code" is -3, API request was made but did not return a result
	 *
	 * @param string $api_url   An API URL which can include certain tags like [list_id] and fill in the value automatically.
	 * @param string $method    One of: GET POST PUT DELETE, etc.
	 * @param array $url_args   Array of args to pass as URL parameters
	 * @param array $body_args  Array of args to pass as body content (like form fields)
	 *
	 * @return array  On success returns an array with parameters: data, response_code, response_message, response
	 *                On failure, returns false. Failure implies that the request did not submit properly or the response was not a json format -- meaning the connection probably failed.
	 */
	function request( $api_url, $method = 'GET', $url_args = array(), $body_args = array() ) {
		
		// Prepare result, filled in as we go
		$response = array(
			'api_url' => $api_url,
			'response_body' => null,
			'response_code' => null,
			'response_message' => null,
			'response' => null,
		);
		
		// Add debug info to the output
		if ( $this->debug_mode ) {
			$response['debug'] = array(
				'final_url' => null,
				'headers' => array(),
				'method' => $method,
				'url_args' => $url_args,
				'body_args' => $body_args,
				'file' => array(
					'path' => __FILE__,
					'line' => __LINE__,
					'function' => __FUNCTION__,
				),
			);
		}
		
		// Require api url
		if ( empty($api_url) ) {
			$response['response_body'] = 'Error: API URL was not provided';
			$response['response_code'] = -1;
			$this->response = $response;
			return false;
		}
		
		$final_url = $api_url;
		
		// Add URL args to api url
		if ( !empty($url_args) ) {
			$final_url = add_query_arg( $url_args, $final_url );
		}
		
		// Prepare headers to send with the request
		$headers = array();
		
		// Use an authorization header?
		if ( $auth_header = $this->get_authorization_header() ) {
			$headers['Authorization'] = $auth_header;
		}
		
		// Prepare args to send to one of the WP HTTP API methods
		$args = array(
			'headers' => $headers,
		);
		
		// Add body args
		if ( $body_args ) {
			$args['body'] = json_encode($body_args);
		}
		
		// Store prepared data for debugging
		if ( $this->debug_mode ) {
			$response['debug']['headers'] = $headers;
			$response['debug']['final_url'] = $final_url;
		}
		
		// Perform the query based on api method
		switch( $method ) {
			case 'PATCH':
				$args['method'] = 'PATCH';
				$response = wp_remote_request($final_url, $args);
				break;
			
			case 'POST':
				$response = wp_remote_post($final_url, $args);
				break;
			
			case 'GET':
				$response = wp_remote_get($final_url, $args);
				break;
			
			default:
				wp_die('Invalid HTTP method "'. $method .'" for mailchimp api in A+A DTL Mailchimp Tags');
				exit;
				break;
		}
		
		// Check if WP_Error
		if ( is_wp_error( $response ) ) {
			$response['response_body'] = 'WP Error [' . $response->get_error_code() . ']: ' . $response->get_error_message();
			$response['response_code'] = -2;
			if ( $this->debug_mode ) $response['debug']['wp_error'] = $response;
			$this->response = $response;
			return false;
		}
		
		// Check if other error
		if ( ! isset( $response['response'] ) || ! is_array( $response['response'] ) ) {
			$response['response_body'] = 'Error: Empty API response';
			$response['response_code'] = -3;
			if ( $this->debug_mode ) $response['debug']['wp_error'] = $response;
			$this->response = $response;
			return false;
		}
		
		// Get response information
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body = wp_remote_retrieve_body( $response );
		
		// Decode JSON into an array
		if ( $response_body ) {
			$j = json_decode( $response_body, true );
			if ( $j ) $response_body = $j;
		}
		
		// Add response parts
		$response['response_body'] = $response_body;
		$response['response_code'] = $response_code;
		$response['response_message'] = $response_message;
		$response['response'] = $response;
		
		// Get the final response
		$this->response = $response;
		return true;
	}
	
	public function get_response() {
		return $this->response;
	}
	
	public function get_response_body() {
		return is_array($this->response) ? $this->response['response_body'] : array();
	}
	
	public function get_response_code() {
		return is_array($this->response) ? $this->response['response_code'] : 0;
	}
	
	public function get_response_message() {
		return is_array($this->response) ? $this->response['response_message'] : '';
	}
	
}