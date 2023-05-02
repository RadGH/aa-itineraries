<?php

class Class_AH_Smartsheet_Invoices  {
	
	public function __construct() {
		
		// When saving or trashing an invoice, queue to be updated in Smartsheet
		add_action( 'save_post', array( $this, 'on_modified_post' ), 40 );
		add_action( 'wp_trash_post', array( $this, 'on_modified_post' ), 40 );
		
		// When saving an invoice, queue to be updated in Smartsheet
		add_action( 'acf/save_post', array( $this, 'acf_on_save_post' ), 40 );
		
		// When updating a field for an invoice, queue to be updated in Smartsheet
		add_action( 'acf/update_value', array( $this, 'acf_on_update_value' ), 40, 3 );
		
		// At the end of processing, process the queue
		add_action( 'shutdown', array( $this, 'on_shutdown_process_queue' ) );
		
		// Process the queue every 5 minutes to stay in sync
		add_action( 'ah_cron/5_minute', array( $this, 'process_queue' ) );
		
		// Ensures the Smartsheet webhook is registered, which sync's Smartsheet changes back to the site.
		add_action( 'init', array( $this, 'register_smartsheet_webhooks' ) );
		
		// Test the queue system by updating an invoice
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_invoice_test_queue
		if ( isset($_GET['ah_smartsheet_invoice_test_queue']) ) add_action( 'init', array( $this, 'ah_smartsheet_invoice_test_queue' ) );
		
		// Test updating a cell in a row when acf saves a field
		// https://alpinehikerdev.wpengine.com/?ah_smartsheet_invoice_update_field&post_id=6118
		if ( isset($_GET['ah_smartsheet_invoice_update_field']) ) add_action( 'init', array( $this, 'ah_smartsheet_invoice_update_field' ) );
		
	}
	
	public function register_smartsheet_webhooks() {
	}
	
	/**
	 * Add an invoice to the queue so that when next processed, the invoice is updated in Smartsheet.
	 * The queue is processed at the end of the PHP process, otherwise in 5 minute intervals using WP Cron.
	 * Using a queue prevents an issue where one invoice is updated multiple times when several fields change in one request.
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	public function add_to_queue( $post_id ) {
		if ( ! AH_Invoice()->is_valid( $post_id ) ) return;
		
		$notices = $this->get_queue();
		$notices[$post_id] = $post_id;
		update_option( 'ah-smartsheet-invoice-queue', $notices, false );
	}
	
	public function remove_from_queue( $post_id ) {
		$queue = $this->get_queue();
		
		if ( $post_id && isset($queue[$post_id]) ) {
			unset($queue[$post_id]);
			update_option( 'ah-smartsheet-invoice-queue', $queue, false );
			return true;
		}else{
			return false;
		}
	}
	
	public function get_queue() {
		$queue = get_option( 'ah-smartsheet-invoice-queue' );
		if ( !is_array($queue) ) $queue = array();
		return $queue;
	}
	
	public function process_queue( $debug = false ) {
		$queue = $this->get_queue();
		
		if ( $queue ) foreach( $queue as $post_id ) {
			if ( $debug ) echo 'Processing invoice #'. $post_id . '<br>';
			$this->remove_from_queue( $post_id );
			$this->update_invoice_row( $post_id );
		}
	}
	
	public function on_shutdown_process_queue() {
		$this->process_queue();
	}
	
	public function update_invoice_row( $post_id ) {
		if ( ! AH_Invoice()->is_valid( $post_id ) ) return;
		
		$smartsheet_id = AH_Smartsheet_API()->get_sheet_id_from_settings( 'invoices' );
		// $webhook_action = AH_Smartsheet_API()->get_webhook_action_from_settings( 'invoices' );
		$column_ids = AH_Smartsheet_API()->get_column_ids_from_settings( 'invoices' );
		
		$post_id_column_id = array_search( 'post_id', $column_ids );
		
		// Get cells for the invoice
		$cell_values = $this->get_post_cells( $post_id );
		
		$cells = array();
		
		foreach( $cell_values as $name => $value ) {
			$column_id = array_search( $name, $column_ids );
			if ( ! $column_id ) continue;
			
			$cells[] = array(
				'columnId' => $column_id,
				'value' => $value,
			);
		}
		
		// Find the row
		$row = AH_Smartsheet_API()->lookup_row_by_column_value( $smartsheet_id, $post_id_column_id, $post_id );
		$row_id = $row ? $row['id'] : false;
		
		// If row is missing, create a new row
		if ( ! $row_id ) $row_id = AH_Smartsheet_API()->insert_row( $smartsheet_id, $cells );
		
		// If failed to insert row, abort
		if ( ! $row_id ) {
			AH_Invoice()->add_log_message( $post_id, 'Smartsheet Error: Could not locate or insert row to sheet.' );
			return;
		}
		
		// Update that row
		$result = AH_Smartsheet_API()->update_row( $smartsheet_id, $row_id, $cells );
		
		// If failed to update row, abort
		if ( ! $result ) {
			AH_Invoice()->add_log_message( $post_id, 'Smartsheet Error: Row was found but could not be updated.' );
			return;
		}
		
	}
	
	/**
	 * Get an array of cells for a post and populate each cell with the corresponding value
	 *
	 * @param $post_id
	 *
	 * @return null[]
	 */
	public function get_post_cells( $post_id ) {
		$cells = array(
			'post_id' => null,
			'status' => null,
			'amount_due' => null,
			'amount_paid' => null,
			'due_date' => null,
			'date_created' => null,
			'name' => null,
			'email' => null,
			'phone_number' => null,
		);
		
		foreach( $cells as $name => $v ) {
			$cells[$name] = $this->get_cell_value( $post_id, $name );
		}
		
		return $cells;
	}
	
	/**
	 * Get the value of a specific cell
	 *
	 * @param $post_id
	 * @param $name
	 *
	 * @return string
	 */
	public function get_cell_value( $post_id, $name ) {
		switch( $name ) {
			case 'post_id':
				return (string) $post_id;
			
			case 'name':
				$first_name = get_post_meta( $post_id, 'first_name', true );
				$last_name = get_post_meta( $post_id, 'last_name', true );
				return (string) trim( $first_name . ' ' . $last_name );
			
			case 'status':
				$post_status = get_post_status( $post_id );
				if ( $post_status == 'trash' ) return 'Trash';
				$invoice_status = get_field( 'invoice_status', $post_id, false );
				return (string) $invoice_status;
			
			case 'date_created':
				return (string) get_post_time( 'm/d/Y g:i:s a', false, $post_id );
				
			case 'due_date':
				$meta_value = (string) get_field( $name, $post_id, false );
				if ( ! $meta_value ) return '';
				$ts = strtotime($meta_value);
				return (string) date('m/d/Y', $ts);
			
			default:
				$meta_value = get_field( $name, $post_id, false );
				return (string) $meta_value;
		}
	}
	
	/**
	 * When saving or trashing an invoice, queue to be updated in Smartsheet
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	public function on_modified_post( $post_id ) {
		if ( ! AH_Invoice()->is_valid( $post_id ) ) return;
		
		$this->add_to_queue( $post_id );
	}
	
	/**
	 * When saving an invoice, queue to be updated in Smartsheet
	 *
	 * @param $acf_id
	 *
	 * @return void
	 */
	public function acf_on_save_post( $acf_id ) {
		$info = acf_get_post_id_info( $acf_id );
		if ( $info['type'] != 'post' ) return;
		if ( ! AH_Invoice()->is_valid( $info['id'] ) ) return;
		
		$this->add_to_queue( $info['id'] );
	}
	
	/**
	 * When updating a field for an invoice, queue to be updated in Smartsheet
	 *
	 * @param $value
	 * @param $acf_id
	 * @param $field
	 *
	 * @return mixed
	 */
	public function acf_on_update_value( $value, $acf_id, $field ) {
		$info = acf_get_post_id_info( $acf_id );
		if ( $info['type'] != 'post' ) return $value;
		if ( ! AH_Invoice()->is_valid( $info['id'] ) ) return $value;
		
		$this->add_to_queue( $info['id'] );
		
		return $value;
	}
	
	// Test the queue system by updating an invoice
	// https://alpinehikerdev.wpengine.com/?ah_smartsheet_invoice_test_queue
	public function ah_smartsheet_invoice_test_queue() {
		$post_id = 6118;
		$this->add_to_queue( $post_id );
		$queue = $this->get_queue();
		
		echo '<p>Items in queue:</p>';
		pre_dump( $queue );
		
		$this->process_queue( true );
		exit;
	}
	
	// Test updating a cell in a row when acf saves a field
	// https://alpinehikerdev.wpengine.com/?ah_smartsheet_invoice_update_field&post_id=6118
	public function ah_smartsheet_invoice_update_field() {
		$post_id = (int) $_GET['post_id'];
		$value = rand(0, 1000) / 100;
		update_field( 'amount_paid', $value, $post_id );
		pre_dump(compact('post_id', 'value'));
		exit;
	}
	
}