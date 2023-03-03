<?php

/*
Formatting cells:
https://smartsheet.redoc.ly/#section/API-Basics/Formatting

HTTP API, Headers, Status codes:
https://smartsheet.redoc.ly/#section/API-Basics/HTTP-and-REST
*/

/**
 * Smartsheet API Manager
 */
class Class_AH_Smartsheet {
	
	// Config
	public $api_key = 'fqqgSHk6vetds8djU915DIa5aRlHzHrmoAu31';
	
	// Variables
	private RS_API $API;
	
	public $api_initialized = false;
	
	// Construct
	public function __construct() {
		
		$this->API = new RS_API();
		
		// Get a list of all sheets
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_view_sheets
		if ( isset($_GET['ah_smartsheet_view_sheets']) ) add_action( 'init', array( $this, 'ah_smartsheet_view_sheets' ) );
		
		// Get a list of sheets matching search term
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_search_sheets
		if ( isset($_GET['ah_smartsheet_search_sheets']) ) add_action( 'init', array( $this, 'ah_smartsheet_search_sheets' ) );
		
		// Get a list of columns within a sheet
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_view_column_ids=7609265092355972
		if ( isset($_GET['ah_smartsheet_view_column_ids']) ) add_action( 'init', array( $this, 'ah_smartsheet_view_column_ids' ) );
		
	}
	
	
	public function get_api() {
		if ( $this->api_initialized ) return $this->API;
		
		$this->API->set_authorization_header( 'Bearer ' . $this->api_key );

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
		return $this->get_api()->request( $api_url, $method, $url_args, $body_args, $headers );
	}
	
	/**
	 * Get the ID of a sheet by key that matches a field from Theme Settings -> Alpine Hikers -> Smartsheet Settings
	 * The $key should be something like "invoices"
	 *
	 * @param $key
	 *
	 * @return void
	 */
	public function get_sheet_id( $key ) {
		$settings = get_field( $key, 'ah_settings' );
		
		return $settings['sheet_id'] ?? false;
	}
	
	public function get_columns( $key ) {
		$settings = get_field( $key, 'ah_settings' );
		
		return $settings['column_ids'] ?? false;
	}
	
	public function search_for_sheet( $search ) {
		$url = 'https://api.smartsheet.com/2.0/search';
		$data = array( 'query' => $search, 'scopes' => 'sheetNames' );
		$body = array();
		$method = 'GET';
		$headers = array( 'Content-Type' => 'application/json' );
		
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
	
	public function ah_smartsheet_view_column_ids() {
		if ( ! current_user_can('administrator') ) aa_die( 'ah_smartsheet_view_column_ids is admin only' );
		
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
		
		exit;
	}
	
	public function ah_smartsheet_search_sheets() {
		if ( ! current_user_can('administrator') ) aa_die( 'ah_smartsheet_search_sheets is admin only' );
		
		$search = (string) stripslashes($_GET['ah_smartsheet_search_sheets']);
		if ( ! $search || $search === '1' || $search === '0' ) $search = 'A+A';
		
		$results = $this->search_for_sheet( $search );
		
		echo '<p><strong>Search results for "'. esc_html( $search ) .'":</strong></p>';
		
		if ( ! $results ) {
			echo '<em>No results found</em>';
			exit;
		}
		
		pre_dump_table( $results );
		
		exit;
	}
	
	public function ah_smartsheet_view_sheets() {
		if ( ! current_user_can('administrator') ) aa_die( 'ah_test_notice is admin only' );
		
		// settings
		$api_key = 'fqqgSHk6vetds8djU915DIa5aRlHzHrmoAu31';
		$auth_header = 'Bearer ' . $api_key;
		
		// perform a request to get all sheets
		$url = 'https://api.smartsheet.com/2.0/sheets';
		$result = $this->get_api()->request( $url );
		
		// get the result
		$body = $result['data'];
		
		// display results
		?>
		<table>
			<tbody>
			<tr>
				<th>Page Number (pageNumber)</th>
				<td><?php echo $body['pageNumber'] ?? ''; ?></td>
			</tr>
			<tr>
				<th>Page Size (pageSize)</th>
				<td><?php echo $body['pageSize'] ?? ''; ?></td>
			</tr>
			<tr>
				<th>Total Pages (totalPages)</th>
				<td><?php echo $body['totalPages'] ?? ''; ?></td>
			</tr>
			<tr>
				<th>Total Sheets (totalCount)</th>
				<td><?php echo $body['totalCount'] ?? ''; ?></td>
			</tr>
			</tbody>
		</table>
		
		<p><strong>Results (data):</strong></p>
		<table>
			<thead>
			<tr>
				<th>id</th>
				<th>name</th>
				<th>accessLevel</th>
				<th>permalink</th>
				<th>createdAt</th>
				<th>modifiedAt</th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach( $body['data'] as $row ) {
				?>
				<tr>
					<td><?php echo $row['id']; ?></td>
					<td><?php echo $row['name']; ?></td>
					<td><?php echo $row['accessLevel']; ?></td>
					<td><?php echo $row['permalink']; ?></td>
					<td><?php echo $row['createdAt']; ?></td>
					<td><?php echo $row['modifiedAt']; ?></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
		
		exit;
	}
	
}