<?php

class Class_AH_Smartsheet_Invoices  {
	
	public function __construct() {
		
		add_action( 'acf/save_post', array( $this, 'acf_on_save_post' ), 40 );
		
		if ( ! current_user_can('administrator') ) aa_die( 'ah_test_notice is admin only' );
		
		// Test row creation into the invoice sheet
		// https://alpinehikerdev.wpengine.com/?ah_insert_invoice_row
		if ( isset($_GET['ah_insert_invoice_row']) ) add_action( 'init', array( $this, 'ah_insert_invoice_row' ) );
	}
	
	public function ah_insert_invoice_row() {
		if ( ! current_user_can('administrator') ) aa_die( 'ah_test_notice is admin only' );
		
		$sheet_id = AH_Plugin()->Smartsheet->get_sheet_id( 'invoices' );
		$columns = AH_Plugin()->Smartsheet->get_columns( 'invoices' );
		
		$cells = array();
		
		foreach( $columns as $name => $column_id ) {
			$cells[] = array(
				'columnId' => $column_id,
				'value' => $name, // just for testing
				// 'name' => $name,
			);
		}
		
		$row_id = AH_Plugin()->Smartsheet->insert_row( $sheet_id, $cells );
		
		pre_dump($row_id);
		exit;
	}
	
	/**
	 * @return Class_Invoice_Post_Type
	 */
	public function Invoice() {
		return AH_Plugin()->Invoice;
	}
	
	public function acf_on_save_post( $acf_id ) {
		$info = acf_get_post_id_info( $acf_id );
		if ( $info['type'] != 'post' ) return;
		
		$this->update_invoice( $info['id'] );
	}
	
	public function update_invoice( $post_id ) {
		if ( ! $this->Invoice()->is_valid_invoice( $post_id ) ) return;
		
		$smartsheet_id = get_post_meta( $post_id, 'smartsheet_id', true );
		
		if ( ! $smartsheet_id ) {
			$smartsheet_id = $this->insert_smartsheet_invoice( $post_id );
			update_post_meta( $post_id, 'smartsheet_id', true );
		}
		
	}
	
	public function insert_smartsheet_invoice( $post_id ) {
	}
	
}