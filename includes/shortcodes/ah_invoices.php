<?php

function shortcode_ah_invoices( $atts, $content = '', $shortcode_name = 'ah_invoices' ) {
	$atts = shortcode_atts(array(
	), $atts, $shortcode_name);
	
	$invoices = AH_Plugin()->Invoice->get_user_invoices();

	if ( ! $invoices->have_posts() ) {
		return 'You currently have no invoices.';
	}
	
	ob_start();
	?>
<div class="ah-invoices">
	
	<table class="ah-invoice-table">
		<thead>
			<tr>
				<th class="col col-id">ID</th>
				<th class="col col-status">Status</th>
				<th class="col col-amount">Amount</th>
				<th class="col col-actions">Actions</th>
			</tr>
		</thead>
		
		<tbody>
			<?php
			foreach( $invoices->posts as $post ) {
				$status = ah_get_invoice_status( $post->ID );
				$amount = ah_get_payment_amount( $post->ID, true );
				
				$invoice_url = ah_get_invoice_page_url( $post->ID );
				
				// Awaiting Payment, Processing, Paid, Payment Failed
				// awaiting-payment, processing, paid, payment-failed
				$status_slug = sanitize_title_with_dashes( strtolower( $status) );
				$status_indicator = ah_get_invoice_status_indicator( $post->ID );
				
				$classes = array('ah-invoice-item');
				$classes[] = 'status-' . $status_slug;
				?>
				<tr class="<?php echo esc_attr(implode(' ', $classes)); ?>">
					<td class="col col-id"><?php
						printf(
							'<a href="%s">%s</a>',
							esc_attr( $invoice_url ),
							esc_html( $post->ID )
						);
					?></td>
					<td class="col col-status"><?php echo $status_indicator; ?> <?php echo $status; ?></td>
					<td class="col col-amount"><?php echo $amount; ?></td>
					<td class="col col-actions"><?php
						
						// Action: View
						printf(
							'<a href="%s" class="button button-small button-secondary">%s</a>',
							esc_attr( $invoice_url ),
							esc_html( 'View' )
						);
						
						// Action: Pay Invoice
						// If status is not paid or processing, the user needs to complete payment.
						if ( ah_does_invoice_need_payment( $post->ID ) ) {
							$form_url = ah_get_invoice_form_url( $post->ID );
							
							printf(
								'<a href="%s" class="button button-small button-secondary">%s</a>',
								esc_attr( $form_url ),
								esc_html( 'Pay Invoice' )
							);
						}
						
					?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	
</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ah_invoices', 'shortcode_ah_invoices' );