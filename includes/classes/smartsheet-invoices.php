<?php

class Class_AH_Smartsheet_Invoices  {
	
	public function __construct() {
		
		add_action( 'acf/save_post', array( $this, 'acf_on_save_post' ), 40 );
		
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
			$smartsheet_id = $this->insert_smartsheet_row( $post_id );
			update_post_meta( $post_id, 'smartsheet_id', true );
		}
		
	}
	
}