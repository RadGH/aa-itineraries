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
 *
 * @param int $invoice_id
 *
 * @return float
 */
function ah_get_invoice_amount_due( $invoice_id ) {
	return AH_Plugin()->Invoice->get_amount_due( $invoice_id );
}

/**
 * Get the payment amount of an invoice.
 *
 * @param int $invoice_id
 *
 * @return string|false
 */
function ah_get_invoice_amount_paid( $invoice_id ) {
	return AH_Plugin()->Invoice->get_amount_paid( $invoice_id );
}

/**
 * Get the payment amount of an invoice.
 *
 * @param int $invoice_id
 *
 * @return string|false
 */
function ah_get_invoice_remaining_balance( $invoice_id ) {
	return AH_Plugin()->Invoice->get_remaining_balance( $invoice_id );
}

/**
 * Get the due date of an invoice
 *
 * @param int $invoice_id
 *
 * @return string
 */
function ah_get_invoice_due_date( $invoice_id ) {
	return AH_Plugin()->Invoice->get_due_date( $invoice_id );
}