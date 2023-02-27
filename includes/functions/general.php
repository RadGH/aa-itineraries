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
	return AH_Plugin()->Invoice->get_invoice_page_url( $invoice_id );
}

/**
 * Get URL to the payment form for a specific invoice
 *
 * @param int $invoice_id
 *
 * @return string|false
 */
function ah_get_invoice_form_url( $invoice_id ) {
	return AH_Plugin()->Invoice->get_invoice_form_url( $invoice_id );
}

/**
 * Return true if an invoice needs payment
 *
 * @param int $invoice_id
 *
 * @return bool
 */
function ah_does_invoice_need_payment( $invoice_id ) {
	return AH_Plugin()->Invoice->does_invoice_need_payment( $invoice_id );
}

/**
 * Get the status of an invoice.
 *
 * @param int $invoice_id
 *
 * @return string|false
 */
function ah_get_invoice_status( $invoice_id ) {
	return AH_Plugin()->Invoice->get_invoice_status( $invoice_id );
}

/**
 * Get the status of an invoice.
 *
 * @param int $invoice_id
 *
 * @return string
 */
function ah_get_invoice_status_indicator( $invoice_id ) {
	return AH_Plugin()->Invoice->get_invoice_status_indicator( $invoice_id );
}

/**
 * Get the payment amount of an invoice.
 * If $currency_format is true, returns formatted as USD currency.
 *
 * @param int $invoice_id
 * @param bool $currency_format
 *
 * @return string|false
 */
function ah_get_payment_amount( $invoice_id, $currency_format = false ) {
	return AH_Plugin()->Invoice->get_payment_amount( $invoice_id, $currency_format );
}

/**
 * Adjust a date such as "+30 days" with standard php date formatting.
 *
 * @param string $offset           "+30 days" or "-1 month"
 * @param string $format           "Y-m-d" or "timestamp"
 * @param null|int $current_time   timestamp, defaults to current time in server timezone
 *
 * @return string|int
 */
function ah_adjust_date( $offset, $format, $current_time = null ) {
	if ( $current_time === null ) $current_time = current_time( 'timestamp' );
	
	$ts = strtotime( $offset, $current_time );
	
	if ( $format === 'timestamp' )
		return $ts;
	else
		return date( $format, $ts );
}