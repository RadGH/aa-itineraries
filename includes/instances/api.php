<?php

/*
Radley's API Interface
Version 1.0
*/

class RS_API {
	
	public $endpoint = '';
	
	private $auth_header = '';
	private $debug_mode = '';
	
	private array $response = array();
	
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
		return $this->auth_header ?: false;
		
	}
	
	/**
	 * Performs an API request. Returns an array including: <bool> success, <array> data, <string> message, <int> code
	 *
	 * If "code" is null, nothing happened.
	 * If "code" is -1, error occurred but the API request was not sent
	 * If "code" is -2, error occurred during the API request
	 * If "code" is -3, API request was made but did not return a result
	 *
	 * @param string $api_url   An API URL which can include certain tags like [list_id] and fill in the value automatically.
	 * @param string $method    One of: GET POST PUT DELETE, etc.
	 * @param array $url_args   Array of args to pass as URL parameters
	 * @param array $body_args  Array of args to pass as body content (like form fields)
	 * @param array $headers    Array of additional headers
	 *
	 * @return array  On success returns an array described above
	 *                On failure, returns the same array structure but ['success'] will be false.
	 */
	function request( $api_url, $method = 'GET', $url_args = array(), $body_args = array(), $headers = array() ) {
		
		// Prepare result, filled in as we go
		$response = array(
			'success' => false,
			'data' => null,
			'message' => '',
			'code' => 0,
			
			'api_url' => $api_url,
			'request' => null,
			'debug' => array(),
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
			$response['message'] = 'Error: API URL was not provided';
			$response['code'] = -1;
			return $response;
		}
		
		$final_url = $api_url;
		
		// Add URL args to api url
		if ( !empty($url_args) ) {
			$final_url = add_query_arg( $url_args, $final_url );
		}
		
		// Prepare headers to send with the request
		if ( ! is_array($headers) ) {
			$headers = array();
		}
		
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
			case 'PUT':
			case 'DELETE':
				$args['method'] = $method;
				$request = wp_remote_request($final_url, $args);
				break;
			
			case 'POST':
				$request = wp_remote_post($final_url, $args);
				break;
			
			case 'GET':
				$request = wp_remote_get($final_url, $args);
				break;
			
			default:
				wp_die('Invalid HTTP method "'. $method .'" for api in ' . __FILE__);
				exit;
				break;
		}
		
		// Check if WP_Error
		if ( is_wp_error( $request ) ) {
			$response['message'] = 'WP Error [' . $request->get_error_code() . ']';
			$response['data'] = $request->get_error_message();
			$response['code'] = -2;
			if ( $this->debug_mode ) $response['debug']['wp_error'] = $response;
			return $response;
		}
		
		// Check if other error
		if ( ! isset( $request['response'] ) || ! is_array( $request['response'] ) ) {
			$response['message'] = 'Error: Empty API response';
			$response['code'] = -3;
			if ( $this->debug_mode ) $response['debug']['wp_error'] = $request;
			return $response;
		}
		
		// Get response information
		$response_code = wp_remote_retrieve_response_code( $request );
		$response_message = wp_remote_retrieve_response_message( $request );
		$response_body = wp_remote_retrieve_body( $request );
		
		// Decode JSON into an array
		if ( $response_body ) {
			$j = json_decode( $response_body, true );
			if ( $j ) $response_body = $j;
		}
		
		$is_success = true;
		if ( is_numeric($response_code) && $response_code >= 400 ) $is_success = false;
		
		// Add response parts
		$response['success'] = $is_success;
		$response['data'] = $response_body;
		$response['code'] = $response_code;
		$response['message'] = $response_message;
		$response['request'] = $request;
		
		return $response;
	}
	
}