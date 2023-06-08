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
	return AH_Invoice()->get_owner( $invoice_id );
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
 * Creates an admin notice that is displayed to any user on the admin dashboard
 *
 * @param $type
 * @param $message
 * @param $data
 * @param $unique_key
 * @param $auto_dismiss
 *
 * @return void
 */
function ah_add_admin_notice( $type, $message, $data = array(), $unique_key = null, $auto_dismiss = false ) {
	AH_Admin()->add_notice( $type, $message, $data, $unique_key, $auto_dismiss );
}

/**
 * Adds a notice to be displayed on the front-end to the current user.
 *
 * @param $type
 * @param $message
 * @param $data
 * @param $unique_key
 * @param $class
 *
 * @return void
 */
function ah_add_theme_notice( $type, $message, $data = array(), $unique_key = null, $class = '' ) {
	AH_Theme()->add_notice( $type, $message, $data, $unique_key, $class );
}

/**
 * Immediately display a single notice on the front-end.
 *
 * @param $type
 * @param $message
 * @param $data
 * @param $class
 *
 * @return void
 */
function ah_display_theme_notice( $type, $message, $data = array(), $class = '' ) {
	AH_Theme()->add_notice( $type, $message, $data, null, $class, true );
}

/**
 * Return TRUE if viewing a PDF in preview mode. Configured in theme.php and stored in pdf.php
 *
 * @return bool|mixed
 */
function ah_is_pdf() {
	return AH_PDF()->use_pdf;
}

/**
 * Return TRUE if viewing a PDF in preview mode. Configured in theme.php and stored in pdf.php
 *
 * @return bool|mixed
 */
function ah_is_pdf_preview() {
	return AH_PDF()->use_preview;
}

/**
 * Gets the hike summary for an itinerary
 *
 * @param $itinerary_id
 *
 * @return string|false
 */
function ah_get_hike_summary( $itinerary_id ) {
	$hikes = get_field( 'hikes', get_the_ID() );
	if ( ah_is_array_recursively_empty($hikes) ) return false;
	
	ob_start();
	
	foreach( $hikes as $i => $s ) {
		$hike_id = (int) $s['hike'];
		$title = get_field( 'hike_name', $hike_id ) ?: get_the_title( $hike_id );
		$links = get_field( 'link_links', $hike_id );
		$slug = get_post_field( 'post_name', $hike_id );
		
		?>
		<h3><a href="#hike-<?php echo esc_attr($slug); ?>"><?php echo $title; ?></a></h3>
		
		<?php
		if ( ! ah_is_array_recursively_empty( $links ) ) {
			foreach( $links as $l ) {
				$label = $l['label'];
				$url = $l['url'];
				?>
				<ul class="hike-list">
					<li><?php echo esc_html($label); ?>:<br><a href="<?php echo esc_attr($url); ?>"><?php echo esc_html($url); ?></a></li>
				</ul>
				<?php
			}
		}
		?>
		
		<?php
	}
	
	return ob_get_clean();
}