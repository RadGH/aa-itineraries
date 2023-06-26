<?php

function shortcode_ah_invoices( $atts, $content = '', $shortcode_name = 'ah_invoices' ) {
	$atts = shortcode_atts(array(
	), $atts, $shortcode_name);
	
	$invoices = AH_Invoice()->get_user_invoices();

	if ( ! $invoices->have_posts() ) {
		return 'You currently have no invoices.';
	}
	
	ob_start();
	?>
	<div class="ah-invoices">
		
		<div class="invoice-list">
			<?php
			while( $invoices->have_posts() ): $invoices->the_post();
				include( AH_PATH . '/templates/content/invoice.php' );
			endwhile;
			
			wp_reset_postdata();
			?>
		</div>
		
	</div>
	<?php
	
	/*
	ob_start();
	?>
<div class="ah-invoices">
	
	<table class="ah-table ah-table-responsive ah-invoice-table" cellspacing="0">
		<thead>
			<tr>
				<th class="col col-id">Invoice No.</th>
				<th class="col col-due-date">Due Date</th>
				<th class="col col-actions">Actions</th>
			</tr>
		</thead>
		
		<tbody>
			<?php
			foreach( $invoices->posts as $post ) {
				$invoice_url = get_permalink( $post->ID );
				$invoice_number = get_field( 'invoice_number', $post->ID );
				$quickbooks_url = get_field( 'quickbooks_url', $post->ID );
				$due_date = get_field( 'due_date', $post->ID );
				?>
				<tr class="ah-invoice-item">
					
					<td class="col col-id" data-mobile-label="Invoice No."><?php
						printf(
							'<a href="%s">%s</a>',
							esc_attr( $invoice_url ),
							esc_html( $invoice_number )
						);
					?></td>
					
					<td class="col col-due-date" data-mobile-label="Due Date"><?php echo ah_format_date( $due_date ); ?></td>
					
					<td class="col col-actions"><?php
						
						// Action: View
						if ( $quickbooks_url ) {
							printf(
								'<a href="%s" class="button button-secondary ah-button button-small">%s</a>',
								esc_attr( $quickbooks_url ),
								esc_html( 'View' )
							);
						}else{
							echo '<em>(Error: Payment link is undefined for #'. $post->ID .')</em>';
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
	*/
	
	return ob_get_clean();
}
add_shortcode( 'ah_invoices', 'shortcode_ah_invoices' );