<?php
$invoice_id = get_the_ID();
$date_created = get_post_field( 'post_date', $invoice_id );
$invoice_number = AH_Invoice()->get_invoice_number( $invoice_id );
$quickbooks_url = AH_Invoice()->get_quickbooks_url( $invoice_id );
?>
<article <?php post_class( 'entry entry-single invoice' ); ?>>
	
	<h3 class="invoice-title"><?php echo get_the_title($invoice_id); ?></h3>
	
	<p><strong>Date Created:</strong> <?php echo ah_format_date( $date_created ); ?></p>
	
	<div class="button-row"><a href="<?php echo esc_attr( $quickbooks_url ); ?>" class="button button-primary"><?php echo esc_html( 'View Invoice' ); ?></a></div>

</article>