<?php

/*
Formatting cells:
https://smartsheet.redoc.ly/#section/API-Basics/Formatting

HTTP API, Headers, Status codes:
https://smartsheet.redoc.ly/#section/API-Basics/HTTP-and-REST
*/

// Available functions:
/*
API:

Settings:
get_column_ids_from_settings( $key )
get_sheet_id_from_settings( $key )

# SHEETS
search_for_sheet( $search )
@todo get_sheet_by_id( $sheet_id )

# ROWS
insert_row( $sheet_id, $cells )
get_row( $sheet_id, $row_id )
update_row( $sheet_id, $row_id, $cells )
lookup_row_by_column_value( $sheet_id, $column_id, $value )

# COLS
get_sheet_columns( $sheet_id )

# CELLS
@todo get_cell( $sheet_id, $column_id, $row_id )
@todo update_cell( $cell_id, $value )

# WEBHOOKS
list_webhooks( $sheet_id )
@todo get_webhook( $sheet_id, $webhook_id ) ?
@todo add_webhook( $sheet_id ) ?
@todo update_webhook( $sheet_id ) ?
*/

/**
 * Smartsheet API Manager
 */
class Class_AH_Smartsheet {
	
	// Config
	public $api_key = null;
	
	// Variables
	private RS_API $API;
	
	public $api_initialized = false;
	
	public $last_request = false;
	
	// Construct
	public function __construct() {
		
		$this->API = new RS_API();
		
		// ----------
		// SHEETS, ROWS, COLUMNS, AND CELLS
		// ----------
		
		// Get a list of all sheets
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_view_sheets
		if ( isset($_GET['ah_smartsheet_view_sheets']) ) add_action( 'init', array( $this, 'ah_smartsheet_view_sheets' ) );
		
		// Get a list of sheets matching search term
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_search_sheets
		if ( isset($_GET['ah_smartsheet_search_sheets']) ) add_action( 'init', array( $this, 'ah_smartsheet_search_sheets' ) );
		
		// Get a list of columns within a sheet
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_view_column_ids=7609265092355972
		if ( isset($_GET['ah_smartsheet_view_column_ids']) ) add_action( 'init', array( $this, 'ah_smartsheet_view_column_ids' ) );
		
		// Test row creation into the invoice sheet
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_insert_invoice_row
		if ( isset($_GET['ah_smartsheet_insert_invoice_row']) ) add_action( 'init', array( $this, 'ah_smartsheet_insert_invoice_row' ) );
		
		// Test row creation into the invoice sheet
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_search_for_row
		if ( isset($_GET['ah_smartsheet_search_for_row']) ) add_action( 'init', array( $this, 'ah_smartsheet_search_for_row' ) );
		
		// Get a row
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_get_row&sheet_id=7609265092355972&row_id=7654311241181060
		if ( isset($_GET['ah_smartsheet_get_row']) ) add_action( 'init', array( $this, 'ah_smartsheet_get_row' ) );
		
		// Update a cell in a row
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_update_row&sheet_id=7609265092355972&row_id=7654311241181060
		if ( isset($_GET['ah_smartsheet_update_row']) ) add_action( 'init', array( $this, 'ah_smartsheet_update_row' ) );
		
		// ----------
		// WEBHOOKS
		// ----------
		
		// Get a list of webhooks
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_list_webhooks&sheet_id=7609265092355972
		if ( isset($_GET['ah_smartsheet_list_webhooks']) ) add_action( 'init', array( $this, 'ah_smartsheet_list_webhooks' ) );
		
		// Add a webhook
		// Example Webhook (not recommended): https://alpinehikerdev.wpengine.com/?ah_smartsheet_add_webhook&sheet_id=7609265092355972&webhook_action=640510876576c&scope=sheet&title=Example%20Webhook
		// Action (invoice): https://alpinehikerdev.wpengine.com/?ah_smartsheet_add_webhook&sheet_id=7609265092355972&webhook_action=invoice&scope=sheet&title=Invoice
		if ( isset($_GET['ah_smartsheet_add_webhook']) ) add_action( 'init', array( $this, 'ah_smartsheet_add_webhook' ) );
		
		// Delete a webhook
		// Webhook ID (not recommended): https://alpinehikerdev.wpengine.com/?ah_smartsheet_delete_webhook&webhook_id=7609265092355972
		// Action (invoice): https://alpinehikerdev.wpengine.com/?ah_smartsheet_delete_webhook&webhook_action=invoice
		if ( isset($_GET['ah_smartsheet_delete_webhook']) ) add_action( 'init', array( $this, 'ah_smartsheet_delete_webhook' ) );
		
		// Enable or disable a webhook
		// Action (invoice): https://alpinehikerdev.wpengine.com/?ah_smartsheet_toggle_webhook&webhook_action=invoice&enabled=1
		if ( isset($_GET['ah_smartsheet_toggle_webhook']) ) add_action( 'init', array( $this, 'ah_smartsheet_toggle_webhook' ) );
		
		// Once enabled, a callback is sent that is handled by smartsheet-webhooks.php -> capture_webhook_callback()
		
		// @todo Update one cell
		// @todo https://alpinehikerdev.wpengine.com/?ah_smartsheet_update_row&sheet_id=7609265092355972&row_id=7654311241181060&cell_id=XXX&value=YYY
		
	}
	
	public function get_api_key() {
		if ( $this->api_key === null ) {
			$this->api_key = get_field( 'smartsheet_api_key', 'ah_smartsheet' );
		}
		
		return $this->api_key;
	}
	
	
	public function get_api() {
		if ( $this->api_initialized ) return $this->API;
		
		$this->API->set_authorization_header( 'Bearer ' . $this->get_api_key() );

		if ( aa_is_developer() ) {
			$this->API->set_debug_mode( true );
		}
		
		$this->api_initialized = true;
		
		return $this->API;
	}
	
	/**
	 * @param $api_url
	 * @param $method
	 * @param $url_args
	 * @param $body_args
	 * @param $headers
	 *
	 * @return array[] {
	 *    @var bool $success,
	 *    @var array $data,
	 *    @var string $message,
	 *    @var int $code,
	 * }
	 */
	public function request( $api_url, $method = 'GET', $url_args = array(), $body_args = array(), $headers = array() ) {
		$result = $this->get_api()->request( $api_url, $method, $url_args, $body_args, $headers );
		$this->last_request = $result;
		return $result;
	}
	
	/**
	 * Get the ID of a sheet by key that matches a field from Theme Settings -> Alpine Hikers -> Smartsheet Settings
	 * The $key should be something like "invoices"
	 *
	 * @param $key
	 *
	 * @return void
	 */
	public function get_sheet_id_from_settings( $key ) {
		$settings = get_field( $key, 'ah_smartsheet' );
		
		return $settings['sheet_id'] ?? false;
	}
	
	/**
	 * Get the ID of a sheet by key that matches a field from Theme Settings -> Alpine Hikers -> Smartsheet Settings
	 * The $key should be something like "invoices"
	 *
	 * @param $key
	 *
	 * @return void
	 */
	public function get_webhook_action_from_settings( $key ) {
		$settings = get_field( $key, 'ah_smartsheet' );
		
		return $settings['webhook_action'] ?? false;
	}
	
	/**
	 * Get an array of column IDs in a key:value pair where the key is the name ("amount_due") and value is the column ID (437...)
	 *
	 * @param $key
	 *
	 * @return array
	 */
	public function get_column_ids_from_settings( $key ) {
		$settings = get_field( $key, 'ah_smartsheet' );
		
		$cols = array();
		
		if ( $settings['column_ids'] ) foreach( $settings['column_ids'] as $name => $column_id ) {
			if ( $column_id ) {
				$cols[ $column_id ] = $name;
			}
		}
		
		return $cols;
	}
	
	public function search_for_sheet( $search ) {
		$url = 'https://api.smartsheet.com/2.0/search';
		$data = array();
		$body = array();
		$method = 'GET';
		$headers = array( 'Content-Type' => 'application/json' );
		
		$data['query'] = $search;
		$data['scopes'] = 'sheetNames';
		
		$result = $this->request( $url, $method, $data, $body, $headers );
		
		if ( ! $result['success'] || empty($result['data']) ) {
			return false;
		}else{
			return $result['data']['results'] ?: array();
		}
	}
	
	/**
	 * Get columns for a sheet. Returns array with:
	 *   pageNumber, pageSize, totalPages, totalCount, data[]
	 *   data items = id, version, index, title, type, primary, validation, width
	 *
	 * @param $sheet_id
	 *
	 * @return array|false
	 */
	public function get_sheet_columns( $sheet_id ) {
		$url = 'https://api.smartsheet.com/2.0/sheets/'. esc_attr($sheet_id) . '/columns';
		$data = array();
		$body = array();
		$method = 'GET';
		$headers = array( 'Content-Type' => 'application/json' );
		
		$result = $this->request( $url, $method, $data, $body, $headers );
		
		if ( ! $result['success'] || empty($result['data']) ) {
			return false;
		}else{
			return $result['data'];
		}
	}
	
	public function insert_row( $sheet_id, $cells ) {
		$url = 'https://api.smartsheet.com/2.0/sheets/'. esc_attr($sheet_id) . '/rows';
		$data = array();
		$body = array();
		$method = 'POST';
		$headers = array( 'Content-Type' => 'application/json' );
		
		$body['cells'] = $cells;
		
		$result = $this->request( $url, $method, $data, $body, $headers );
		
		// If no cells inserted:
		// {"message":"SUCCESS","resultCode":0,"result":[]}
		
		// If seven cells inserted:
		// "{"message":"SUCCESS","resultCode":0,"result":{"id":6850603112720260,"sheetId":7609265092355972,"rowNumber":2,"siblingId":6726355748644740,"expanded":true,"createdAt":"2023-03-03T19:55:12Z","modifiedAt":"2023-03-03T19:55:12Z","cells":[{"columnId":4375676381357956,"value":"post_id","displayValue":"post_id"},{"columnId":83182986520452,"value":"amount_due","displayValue":"amount_due"},{"columnId":4381638987147140,"value":"amount_paid","displayValue":"amount_paid"},{"columnId":89145592309636,"value":"due_date","displayValue":"due_date"},{"columnId":2334982800205700,"value":"date_created","displayValue":"date_created"},{"columnId":6838582427576196,"value":"name","displayValue":"name"},{"columnId":8885238614517636,"value":"email","displayValue":"email"},{"columnId":4592745219680132,"value":"phone_number","displayValue":"phone_number"}]},"version":5}"
		
		if ( ! $result['success'] || empty($result['data']) ) {
			return false;
		}else{
			return $result['data']['result']['id'] ?? false;
		}
	}
	
	public function get_row( $sheet_id, $row_id ) {
		// @see https://smartsheet.redoc.ly/tag/rows#operation/row-get
		$url = 'https://api.smartsheet.com/2.0/sheets/'. esc_attr($sheet_id) . '/rows/' . esc_attr($row_id);
		$data = array();
		$body = array();
		$method = 'GET';
		$headers = array( 'Content-Type' => 'application/json' );
		
		$data['include'] = array('columns', 'objectValue');
		
		$result = $this->request( $url, $method, $data, $body, $headers );
		
		// Examples:
		// $row_id = $result['data']['id'];
		// $row_number = $result['data']['rowNumber'];
		// $cells = $result['data']['cells'];
		// All properties: https://s3.us-west-2.amazonaws.com/elasticbeanstalk-us-west-2-868470985522/ShareX/2023/03/2023-03-04_11-14-04.txt
		
		if ( ! $result['success'] || empty($result['data']) ) {
			return false;
		}else{
			return $result['data'] ?? false;
		}
	}
	
	public function update_row( $sheet_id, $row_id, $cells ) {
		// @see https://smartsheet.redoc.ly/tag/rows#operation/update-rows
		$url = 'https://api.smartsheet.com/2.0/sheets/'. esc_attr($sheet_id) . '/rows';
		$data = array();
		$body = array();
		$method = 'PUT';
		$headers = array( 'Content-Type' => 'application/json' );
		
		$body['id'] = $row_id;
		$body['cells'] = $cells;
		
		$result = $this->request( $url, $method, $data, $body, $headers );
		
		// $result['data'] = array( 'results' => array( text, objectType, objectId, ... ), 'totalCount' => 1 );
		// more: https://radleysustaire.com/s3/54f865/
		
		if ( ! $result['success'] || empty($result['data']) ) {
			return false;
		}else{
			return $result['data'] ?? false;
		}
	}
	
	/**
	 * Searches for a row based on the value of a column.
	 *
	 * This is done in two steps:
	 * 1. Search the sheet for the text ANYWHERE in the sheet, which returns multiple rows.
	 * 2. Get each row found and look up the columns
	 * 3. Find the first row where the column matches the search value
	 *
	 * Returns an array with all the row data: id, sheetId, rowNumber, cells, etc.
	 *
	 * @param $sheet_id
	 * @param $column_id
	 * @param $search_value
	 * @param $exact_match
	 *
	 * @return array|false
	 */
	public function lookup_row_by_column_value( $sheet_id, $column_id, $search_value, $exact_match = true ) {
		// @see https://smartsheet.redoc.ly/tag/search#operation/list-search-sheet
		$url = 'https://api.smartsheet.com/2.0/search/sheets/'. esc_attr($sheet_id);
		$data = array();
		$body = array();
		$method = 'GET';
		$headers = array( 'Content-Type' => 'application/json' );
		
		$search_value = (string) $search_value;
		$data['query'] = $search_value;
		
		$result = $this->request( $url, $method, $data, $body, $headers );
		
		if ( ! $result['success'] || empty($result['data']) ) {
			return false;
		}
		
		// $result_count = $result['data']['totalCount'];
		
		// Our results contain among other things, each row that has the value in it.
		// However, it does not tell us what column that value lies in, so we need to find that
		$search_results = $result['data']['results'];
		$found_rows = array();
		
		// Loop through each result and pick out any ROW that was found
		if ( $search_results ) foreach( $search_results as $k => $r ) {
			$object_type = $r['objectType']; // "row"
			$object_id = $r['objectId']; // 7654311241181060
			$text = (string) $r['text']; // "6118"
			
			// Is this a row?
			if ( $object_type != 'row' ) continue;
			
			// Is this in the correct column? EDIT: CANT CHECK THIS HERE, COLUMN NOT PROVIDED
			// if ( $object_id != $column_id ) continue;
			
			// Is this an exact match?
			if ( $exact_match && ( $text !== $search_value ) ) continue;
			
			// Found column ID!
			$found_rows[] = (string) $object_id;
		}
		
		// Loop through each row that has our search string then confirm that string lies within the target column
		if ( $found_rows ) foreach( $found_rows as $row_id ) {
			$row = $this->get_row( $sheet_id, $row_id );
			if ( !$row ) continue;
			
			// Find the column, then check the value
			foreach( $row['cells'] as $i => $c ) {
				$cell_value = (string) $c['value'];
				$cell_column_id = $c['columnId'];
				
				if ( $cell_column_id == $column_id ) {
					if ( $exact_match ) {
						if ( $cell_value === $search_value ) return $row;
					}else{
						if ( str_contains( $cell_value, $search_value ) ) return $row;
					}
				}
			}
		}
		
		// Did not find any rows with matching column value
		return false;
	}
	
	/**
	 * Returns an array of webhooks currently registered
	 *
	 * @param $sheet_id
	 *
	 * @return array|false
	 */
	public function list_webhooks( $sheet_id ) {
		// @see https://smartsheet.redoc.ly/tag/webhooks
		$url = 'https://api.smartsheet.com/2.0/webhooks';
		$data = array();
		$body = array();
		$method = 'GET';
		$headers = array( 'Content-Type' => 'application/json' );
		
		$data['includeAll'] = true;
		
		$result = $this->request( $url, $method, $data, $body, $headers );
		
		// $result['data'] = array( pageNumber = 0, pageSize = 100, totalPages = 0, totalCount = 0, data = array() )
		
		if ( ! $result['success'] || empty($result['data']) ) {
			return false;
		}else{
			return $result['data'] ?? false;
		}
	}
	
	/**
	 * Registers a new webhook. Webhooks are not enabled by default. Returns an array from data->result explained in a comment inside.
	 *
	 * @param int $object_id  ID of the sheet/row/cell/etc
	 * @param string $scope   Type of the ID above, such as "sheet"
	 * @param string $title    Name
	 * @param string $action
	 * @param bool $enabled
	 *
	 * @return array|false
	 */
	public function add_webhook( $object_id, $scope, $title, $action, $enabled = true ) {
		// @see https://smartsheet.redoc.ly/tag/webhooks#operation/createWebhook
		$url = 'https://api.smartsheet.com/2.0/webhooks';
		$data = array();
		$body = array();
		$method = 'POST';
		$headers = array( 'Content-Type' => 'application/json' );
		
		$callback_url = site_url('/smartsheet/'. urlencode($action) .'/');
		
		$body['callbackUrl'] = $callback_url;
		$body['name'] = $title;
		$body['version'] = 1;
		$body['scopeObjectId'] = $object_id;
		$body['scope'] = $scope;
		$body['events'] = array( '*.*' );
		
		$result = $this->request( $url, $method, $data, $body, $headers );
		
		// data: https://radleysustaire.com/s3/8fa39a/
		// $result['data'] = array(): message (SUCCESS), resultCode (0), result (array)
		
		// $result['data']['result'] = array() with keys:
		// id, name, scope, scopeObjectId, subscope, events, callbackUrl,
		// sharedSecret, enabled, status, version, createdAt, modifiedAt
		
		if ( ! $result['success'] || empty($result['data']) ) {
			return false;
		}else{
			return $result['data']['result'] ?? false;
		}
	}
	
	/**
	 * Deletes an existing webhook
	 *
	 * @param int $webhook_id
	 *
	 * @return array|false
	 */
	public function delete_webhook( $webhook_id ) {
		// @see https://smartsheet.redoc.ly/tag/webhooks#operation/deleteWebhook
		$url = 'https://api.smartsheet.com/2.0/webhooks/' . urlencode($webhook_id);
		$data = array();
		$body = array();
		$method = 'DELETE';
		$headers = array( 'Content-Type' => 'application/json' );
		
		$result = $this->request( $url, $method, $data, $body, $headers );
		
		if ( ! $result['success'] || empty($result['data']) ) {
			return false;
		}else{
			return $result['data'] ?? false;
		}
	}
	
	/**
	 * Toggle a webhook to be enabled or disabled
	 *
	 * @param int $webhook_id
	 * @param bool $enabled
	 *
	 * @return array|false
	 */
	public function toggle_webhook( $webhook_id, $enabled ) {
		// @see https://smartsheet.redoc.ly/tag/webhooks#operation/updateWebhook
		$url = 'https://api.smartsheet.com/2.0/webhooks/' . urlencode($webhook_id);
		$data = array();
		$body = array();
		$method = 'PUT';
		$headers = array( 'Content-Type' => 'application/json' );
		
		$body['enabled'] = $enabled;
		
		$result = $this->request( $url, $method, $data, $body, $headers );
		
		// pre_dump($result);
		
		// $result['data'] = https://radleysustaire.com/s3/8de5e6/
		// result['data']['result'] = array: id, name, scope, ...
		
		if ( ! $result['success'] || empty($result['data']) ) {
			return false;
		}else{
			return $result['data']['result'] ?? false;
		}
	}
	
	public function ah_smartsheet_view_sheets() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		// settings
		$api_key = $this->get_api_key();
		$auth_header = 'Bearer ' . $api_key;
		
		// perform a request to get all sheets
		$url = 'https://api.smartsheet.com/2.0/sheets';
		$result = $this->get_api()->request( $url );
		
		// get the result
		$body = $result['data'];
		
		// display data
		pre_dump(array(
			'pageNumber' => $body['pageNumber'],
			'pageSize'   => $body['pageSize'],
			'totalPages' => $body['totalPages'],
			'totalCount' => $body['totalCount'],
		));
		
		// display sheets
		pre_dump_table( $body['data'] );
		
		echo '<p><strong>Last request:</strong></p>';
		pre_dump( $this->last_request );
		
		exit;
	}
	
	public function ah_smartsheet_search_sheets() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		$search = (string) stripslashes($_GET['ah_smartsheet_search_sheets']);
		if ( ! $search || $search === '1' || $search === '0' ) $search = 'A+A';
		
		$results = $this->search_for_sheet( $search );
		
		echo '<p><strong>Search results for "'. esc_html( $search ) .'":</strong></p>';
		
		if ( ! $results ) {
			echo '<em>No results found</em>';
			exit;
		}
		
		pre_dump_table( $results );
		
		echo '<p><strong>Last request:</strong></p>';
		pre_dump( $this->last_request );
		
		exit;
	}
	
	public function ah_smartsheet_view_column_ids() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		$sheet_id = (string) stripslashes($_GET['ah_smartsheet_view_column_ids']);
		
		$results = $this->get_sheet_columns( $sheet_id );
		
		echo '<p><strong>Columns for sheet #'. esc_html( $sheet_id ) .':</strong></p>';
		
		if ( ! $results ) {
			echo '<em>No results found</em>';
			exit;
		}
		
		pre_dump(array(
			'pageNumber' => $results['pageNumber'],
			'pageSize' => $results['pageSize'],
			'totalPages' => $results['totalPages'],
			'totalCount' => $results['totalCount'],
		));
		
		pre_dump_table( $results['data'] );
		
		echo '<p><strong>Last request:</strong></p>';
		pre_dump( $this->last_request );
		
		exit;
	}
	
	public function ah_smartsheet_insert_invoice_row() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		try {
			
			$sheet_id = AH_Smartsheet()->get_sheet_id_from_settings( 'invoices' );
			$column_ids = AH_Smartsheet()->get_column_ids_from_settings( 'invoices' );
			if ( !$sheet_id || !$column_ids ) throw new Exception( 'Sheet or column IDs not provided in settings.');
			
			$sheet_cols = AH_Smartsheet()->get_sheet_columns( $sheet_id );
			if ( !$sheet_cols ) throw new Exception( 'API could not load column settings from Smartsheet.');
			
			$cells = array();
			
			// Loop through each column that came from Smartsheet
			foreach( $sheet_cols['data'] as $c ) {
				$column_id = $c['id']; // 4375676381357956
				// $title = $c['title'];  // Invoice Post ID
				// $type = $c['type'];    // TEXT_NUMBER
				
				// Column id is the key: array( 1231454363 => 'first_name' );
				// We might not have all columns in which case the value should be blank.
				$key = $column_ids[ $column_id ] ?? '';
				
				$cells[ $column_id ] = array(
					'columnId' => $column_id,
					'value' => $key,
				);
			}
			
			$row_id = AH_Smartsheet()->insert_row( $sheet_id, $cells );
			if ( ! $row_id ) throw new Exception( 'Row could not be inserted');
			
			echo '<strong>Success: Row inserted</strong>';
			
		} catch( Exception $e ) {
			// AH_Plugin()->Admin->add_notice( 'error', 'Failed to insert row in smartsheet for test invoice ('. __FUNCTION__ .').' . "\n\n" . $e->getMessage(), array('Exception' => $e, 'Sheet ID' => $sheet_id) );
			echo '<strong>Error: '. $e->getMessage() .'</strong>';
		}
		
		pre_dump(compact('row_id', 'sheet_id', 'cells'));
		
		echo '<p><strong>Last request:</strong></p>';
		pre_dump( $this->last_request );
		exit;
	}

	public function ah_smartsheet_search_for_row() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		try {
			
			$sheet_id = AH_Smartsheet()->get_sheet_id_from_settings( 'invoices' );
			$column_ids = AH_Smartsheet()->get_column_ids_from_settings( 'invoices' );
			if ( !$sheet_id || !$column_ids ) throw new Exception( 'Sheet or column IDs not provided in settings.');
			
			$post_id_column_id = $column_ids['post_id'];
			$search = '6118';
			
			$row_id = AH_Smartsheet()->lookup_row_by_column_value( $sheet_id, $post_id_column_id, $search );
			if ( ! $row_id ) throw new Exception( 'Row lookup failed');
			
			echo '<strong>Success: Row lookup success</strong>';
			
		} catch( Exception $e ) {
			echo '<strong>Error: '. $e->getMessage() .'</strong>';
		}
		
		pre_dump(compact('sheet_id', 'column_ids', 'post_id_column_id', 'search', 'row_id'));
		
		echo '<p><strong>Last request:</strong></p>';
		pre_dump( $this->last_request );
		exit;
	}

	public function ah_smartsheet_get_row() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		try {
			
			$sheet_id = (int) $_GET['sheet_id'];
			$row_id = (int) $_GET['row_id'];
			
			$row = AH_Smartsheet()->get_row( $sheet_id, $row_id );
			if ( ! $row ) throw new Exception( 'No results');
			
			echo '<strong>Success: Row found</strong>';
			
		} catch( Exception $e ) {
			echo '<strong>Error: '. $e->getMessage() .'</strong>';
		}
		
		pre_dump(compact('sheet_id', 'row_id', 'row'));
		
		echo '<p><strong>Last request:</strong></p>';
		pre_dump( $this->last_request );
		exit;
	}

	public function ah_smartsheet_update_row() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		try {
			
			$sheet_id = (int) $_GET['sheet_id'];
			$row_id = (int) $_GET['row_id'];
			
			// Get cells for the invoice
			$cells = array(
				array(
					'columnId' => 4375676381357956, // invoice - post_id
					'value' => rand(10000,99999),
				),
				array(
					'columnId' => 8468890121987972,
					'value' => 'status',
				),
				array(
					'columnId' => 83182986520452,
					'value' => 'amount_due',
				),
				array(
					'columnId' => 4381638987147140,
					'value' => 'amount_paid',
				),
				array(
					'columnId' => 89145592309636,
					'value' => 'due_date',
				),
				array(
					'columnId' => 2334982800205700,
					'value' => 'date_created',
				),
				array(
					'columnId' => 6838582427576196,
					'value' => 'name',
				),
				array(
					'columnId' => 8885238614517636,
					'value' => 'email',
				),
				array(
					'columnId' => 4592745219680132,
					'value' => 'phone_number',
				),
			);
			
			$result = AH_Smartsheet()->update_row( $sheet_id, $row_id, $cells );
			if ( ! $result ) throw new Exception( 'Update failed');
			
			echo '<strong>Success: Row updated</strong>';
			
		} catch( Exception $e ) {
			echo '<strong>Error: '. $e->getMessage() .'</strong>';
		}
		
		pre_dump(compact('sheet_id', 'row_id', 'cells', 'result'));
		
		echo '<p><strong>Last request:</strong></p>';
		pre_dump( $this->last_request );
		exit;
	}
	
	
	// Get a list of webhooks
	// https://alpinehikerdev.wpengine.com/?ah_smartsheet_list_webhooks&sheet_id=7609265092355972
	public function ah_smartsheet_list_webhooks() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		try {
			
			$sheet_id = (int) $_GET['sheet_id'];
			
			$webhooks = AH_Smartsheet()->list_webhooks( $sheet_id );
			if ( ! $webhooks ) throw new Exception( 'Failed to get list of webhooks');
			
		} catch( Exception $e ) {
			echo '<strong>Error: '. $e->getMessage() .'</strong>';
		}
		
		pre_dump(compact('sheet_id', 'webhooks'));
		
		echo '<p><strong>Last request:</strong></p>';
		pre_dump( $this->last_request );
		exit;
	}
	
	// Add a webhook
	// https://alpinehikerdev.wpengine.com/?ah_smartsheet_add_webhook&sheet_id=7609265092355972&webhook_action=640510876576c&scope=sheet&title=Example%20Webhook
	// Invoice: https://alpinehikerdev.wpengine.com/?ah_smartsheet_add_webhook&sheet_id=7609265092355972&webhook_action=invoice&scope=sheet&title=Invoice
	public function ah_smartsheet_add_webhook() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		try {
			
			$sheet_id = (int) $_GET['sheet_id'];
			$scope = $_GET['scope'] ?? 'sheet';
			$action = $_GET['webhook_action'] ?? '640510876576c';
			$title = $_GET['title'] ?? ('Example Webhook ' . $action);
			
			$saved_webhook = AH_Smartsheet_Webhooks()->get_saved_webhook_from_action( $action );
			if ( $saved_webhook ) throw new Exception( 'Webhook already exists with the action "'. esc_html($action) .'"');
			
			// webhook callback path:
			// /smartsheet/SOMETHING/
			
			// secret: 1oc4lqv5g8wrpblfefvwyprhrz
			
			$result = AH_Smartsheet()->add_webhook( $sheet_id, $scope, $title, $action );
			if ( ! $result ) throw new Exception( 'Could not insert webhook');
			
			$webhook_id = $result['id'];
			$secret = $result['sharedSecret'];
			
			AH_Smartsheet_Webhooks()->add_saved_webhook( $action, $webhook_id, $secret, $sheet_id, $scope, $title );
			
		} catch( Exception $e ) {
			echo '<strong>Error: '. $e->getMessage() .'</strong>';
		}
		
		pre_dump(compact('sheet_id', 'scope', 'action', 'title', 'saved_webhook', 'result'));
		
		echo '<p><strong>Last request:</strong></p>';
		pre_dump( $this->last_request );
		exit;
	}
	
	// Delete a webhook
	// https://alpinehikerdev.wpengine.com/?ah_smartsheet_delete_webhook&webhook_action=640510876576c
	// https://alpinehikerdev.wpengine.com/?ah_smartsheet_delete_webhook&webhook_id=7322288048629636
	public function ah_smartsheet_delete_webhook() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		try {
			
			$action = $_GET['webhook_action'] ?? false;
			$webhook_id = $_GET['webhook_id'] ?? false;
			
			// If no id specified, delete by action instead
			if ( $webhook_id ) {
				$saved_webhook = AH_Smartsheet_Webhooks()->get_saved_webhook( $webhook_id );
			}else{
				$saved_webhook = AH_Smartsheet_Webhooks()->get_saved_webhook_from_action( $action );
			}
			
			if ( ! $saved_webhook ) throw new Exception( 'Webhook is not registered within the website');
				
			$action = $saved_webhook['action'];
			$webhook_id = $saved_webhook['webhook_id'];
			
			$result = AH_Smartsheet()->delete_webhook( $webhook_id );
			if ( ! $result ) throw new Exception( 'Could not delete webhook');
			
			AH_Smartsheet_Webhooks()->delete_saved_webhook( $webhook_id );
			
		} catch( Exception $e ) {
			echo '<strong>Error: '. $e->getMessage() .'</strong>';
		}
		
		pre_dump(compact('action', 'saved_webhook', 'webhook_id', 'result'));
		
		echo '<p><strong>Last request:</strong></p>';
		pre_dump( $this->last_request );
		exit;
	}
	
	// Enable or disable a webhook
	// https://alpinehikerdev.wpengine.com/?ah_smartsheet_toggle_webhook&webhook_action=invoice&enabled=1
	public function ah_smartsheet_toggle_webhook() {
		if ( ! current_user_can('administrator') ) aa_die( __FUNCTION__ . ' is admin only' );
		
		try {
			
			$action = $_GET['webhook_action'] ?? 'invoice';
			$enabled = ! empty($_GET['enabled']);
			
			$saved_webhook = AH_Smartsheet_Webhooks()->get_saved_webhook_from_action( $action );
			if ( ! $saved_webhook ) throw new Exception( 'Webhook is not registered within the website');
			
			$webhook_id = $saved_webhook['webhook_id'];
			
			$result = AH_Smartsheet()->toggle_webhook( $webhook_id, $enabled );
			if ( ! $result ) throw new Exception( 'Could not toggle webhook');
			
		} catch( Exception $e ) {
			echo '<strong>Error: '. $e->getMessage() .'</strong>';
		}
		
		pre_dump(compact('action', 'enabled', 'saved_webhook', 'webhook_id', 'result'));
		
		echo '<p><strong>Last request:</strong></p>';
		pre_dump( $this->last_request );
		exit;
	}
	
}