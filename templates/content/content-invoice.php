<?php
$invoice_id = get_the_ID();

$status = ah_get_invoice_status( $invoice_id );
$status_indicator = ah_get_invoice_status_indicator( $invoice_id );

$amount_due = ah_get_invoice_amount_due( $invoice_id );
$amount_paid = ah_get_invoice_amount_paid( $invoice_id );
$due_date = ah_get_invoice_due_date( $invoice_id, 'm/d/Y' );

$form_url = ah_get_invoice_form_url( $invoice_id );

$owner_id = get_field( 'user', get_the_ID() );
$user_id = get_current_user_id();

if ( $owner_id != $user_id ) {
	$user_name = ah_get_user_full_name( $owner_id );
	$edit_user_link = sprintf( '<a href="%s">%s</a>', esc_attr(get_edit_user_link($owner_id)), esc_html($user_name));
	
	if ( current_user_can( 'administrator' ) ) {
		if ( $owner_id ) {
			ah_add_theme_notice( 'warning', '<strong>ADMIN NOTICE:</strong> You are viewing an invoice that belongs to another user ('.$edit_user_link.').');
		}else{
			ah_add_theme_notice( 'warning', '<strong>ADMIN NOTICE:</strong> You are viewing an invoice which is not assigned to a user.');
		}
	}else{
		ah_add_theme_notice( 'error', 'You do not have access to view this invoice.');
		return;
	}
}
?>
<article <?php post_class( 'entry entry-single invoice' ); ?>>
	
	<p><strong>Invoice Number:</strong> <?php echo get_the_ID(); ?></p>
	
	<p><strong>Status:</strong> <?php echo $status_indicator, ' ', $status; ?></p>
	
	<p><strong>Amount Due:</strong> <?php echo ah_format_price( $amount_due ); ?></p>
	
	<?php if ( $amount_paid > 0 ) { ?>
	<p><strong>Amount Paid:</strong> <?php echo ah_format_price( $amount_paid ); ?></p>
	<?php } ?>
	
	<?php
	if ( ah_does_invoice_need_payment( $invoice_id ) ) {
		?>
		<p><strong>Due On:</strong> <?php echo $due_date; ?></p>
		<p><?php printf(
			'<a href="%s" class="button button-secondary">%s</a>',
			esc_attr( $form_url ),
			esc_html( 'Pay Invoice' )
		); ?></p>
		<?php
	}
	?>
	
	<?php
	if ( current_user_can( 'administrator' ) ) {
		?>
		<div class="invoice-additional-details">
		
			<h3>Advanced Details (Admin-Only)</h3>
			
			<table>
				<tbody>
				<tr><th>user</th><td><?php echo get_field( 'user', $invoice_id ); ?></td></tr>
				<tr><th>invoice_status</th><td><?php echo get_field( 'invoice_status', $invoice_id ); ?></td></tr>
				<tr><th>amount_due</th><td><?php echo get_field( 'amount_due', $invoice_id ); ?></td></tr>
				<tr><th>due_date</th><td><?php echo get_field( 'due_date', $invoice_id ); ?></td></tr>
				<tr><th>amount_paid</th><td><?php echo get_field( 'amount_paid', $invoice_id ); ?></td></tr>
				<tr><th>entry_id</th><td><?php echo get_field( 'entry_id', $invoice_id ); ?></td></tr>
				
				<tr><th>first_name</th><td><?php echo get_field( 'first_name', $invoice_id ); ?></td></tr>
				<tr><th>last_name</th><td><?php echo get_field( 'last_name', $invoice_id ); ?></td></tr>
				<tr><th>email</th><td><?php echo get_field( 'email', $invoice_id ); ?></td></tr>
				<tr><th>phone_number</th><td><?php echo get_field( 'phone_number', $invoice_id ); ?></td></tr>
				<?php /* <tr><th>username</th><td><?php echo get_field( 'username', $invoice_id ); ?></td></tr> */ ?>
				<?php /* <tr><th>password</th><td><?php echo get_field( 'password', $invoice_id ); ?></td></tr> */ ?>
				<tr><th>address</th><td><?php echo get_field( 'address', $invoice_id ); ?></td></tr>
				<tr><th>address_2</th><td><?php echo get_field( 'address_2', $invoice_id ); ?></td></tr>
				<tr><th>city</th><td><?php echo get_field( 'city', $invoice_id ); ?></td></tr>
				<tr><th>state</th><td><?php echo get_field( 'state', $invoice_id ); ?></td></tr>
				<tr><th>zip</th><td><?php echo get_field( 'zip', $invoice_id ); ?></td></tr>
				<tr><th>tour_date</th><td><?php echo get_field( 'tour_date', $invoice_id ); ?></td></tr>
				<tr><th>log</th><td><pre style="font-size: 12px; white-space: pre-line;"><?php echo get_field( 'log', $invoice_id ); ?></pre></td></tr>
				</tbody>
			</table>
			
		</div>
		<?php
	}
	?>

</article>