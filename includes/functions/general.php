<?php

/**
 * Get the page which has the invoice form embedded in it
 * Setting comes from Invoices -> Settings
 *
 * @return int|false
 */
function ah_get_invoice_page_id() {
	return (int) get_field( 'invoice_page', 'ah_invoices' ) ?: false;
}

/**
 * Get URL to an invoice page (NOT the form)
 *
 * @param int $invoice_id
 *
 * @return string|false
 */
function ah_get_invoice_page_url( $invoice_id ) {
	return AH_Invoice()->get_invoice_page_url( $invoice_id );
}

/**
 * Get URL to the payment form for a specific invoice
 *
 * @param int $invoice_id
 *
 * @return string|false
 */
function ah_get_invoice_form_url( $invoice_id ) {
	return AH_Invoice()->get_invoice_form_url( $invoice_id );
}

/**
 * Return true if an invoice needs payment
 *
 * @param int $invoice_id
 *
 * @return bool
 */
function ah_does_invoice_need_payment( $invoice_id ) {
	return AH_Invoice()->does_invoice_need_payment( $invoice_id );
}

/**
 * Get the status of an invoice.
 *
 * @param int $invoice_id
 *
 * @return string|false
 */
function ah_get_invoice_status( $invoice_id ) {
	return AH_Invoice()->get_invoice_status( $invoice_id );
}

/**
 * Get the status of an invoice.
 *
 * @param int $invoice_id
 *
 * @return string
 */
function ah_get_invoice_status_indicator( $invoice_id ) {
	return AH_Invoice()->get_invoice_status_indicator( $invoice_id );
}

/**
 * Get the payment amount of an invoice.
 *
 * @param int $invoice_id
 *
 * @return float
 */
function ah_get_invoice_amount_due( $invoice_id ) {
	return AH_Invoice()->get_amount_due( $invoice_id );
}

/**
 * Get the payment amount of an invoice.
 *
 * @param int $invoice_id
 *
 * @return string|false
 */
function ah_get_invoice_amount_paid( $invoice_id ) {
	return AH_Invoice()->get_amount_paid( $invoice_id );
}

/**
 * Get the payment amount of an invoice.
 *
 * @param int $invoice_id
 *
 * @return string|false
 */
function ah_get_invoice_remaining_balance( $invoice_id ) {
	return AH_Invoice()->get_remaining_balance( $invoice_id );
}

/**
 * Get the due date of an invoice, optionally using a PHP date $format
 *
 * @param int $invoice_id
 * @param string $format
 *
 * @return string
 */
function ah_get_invoice_due_date( $invoice_id, $format = 'Y-m-d' ) {
	return AH_Invoice()->get_due_date( $invoice_id, $format );
}

/**
 * Get the owner of an invoice (user ID)
 *
 * @param $invoice_id
 *
 * @return int|false
 */
function ah_get_invoice_owner( $invoice_id ) {
	return AH_Invoice()->get_owner_user_id( $invoice_id );
}

/**
 * Get merge tags for an invoice (includes general merge tags)
 *
 * @param int|string $invoice_id   Post ID or to use placeholder values specify "placeholders"
 *
 * @return string[]
 */
function ah_get_invoice_merge_tags( $invoice_id = null ) {
	return AH_Invoice()->get_merge_tags( $invoice_id );
}

/**
 * Get generic merge tags that can be used for invoice emails, or other things
 *
 * @return string[]
 */
function ah_get_general_merge_tags() {
	$site_url = site_url('/');
	$account_url = site_url('/account/');
	
	return array(
		'[site_url]' => $site_url,
		'[account_url]' => $account_url,
	);
}

/**
 * Get the URL that a document links to
 *
 * @param $post_id
 *
 * @return string
 */
function ah_get_document_redirect_url( $post_id ) {
	return untrailingslashit( get_permalink( $post_id )) . '/download/';
}

/**
 * Get the attachment ID of the preview image to use for the document
 *
 * @param $post_id
 *
 * @return int|false
 */
function ah_get_document_preview_image( $post_id ) {
	$attachment_id = (int) get_field( 'preview_image', $post_id, false );
	return $attachment_id ?: false;
}