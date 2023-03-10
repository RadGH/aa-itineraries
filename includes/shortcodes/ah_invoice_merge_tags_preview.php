<?php

function ah_invoice_merge_tags_shortcode_in_acf( $field ) {
	// Do not filter when editing the field group
	if ( acf_is_screen( 'acf-field-group' ) ) return $field;
	
	// Fields to support shortcodes:
	if ( $field['instructions'] ) $field['instructions'] = do_shortcode( $field['instructions'] );
	if ( $field['message'] ) $field['message'] = do_shortcode( $field['message'] );
	
	return $field;
}
add_filter( 'acf/load_field/key=field_63fe7185ed6b3', 'ah_invoice_merge_tags_shortcode_in_acf' ); // Invoice Settings: Reminder Emails -> Merge Tags (message)

function shortcode_ah_invoice_merge_tags_preview( $atts, $content = '', $shortcode_name = 'ah_invoice_merge_tags_preview' ) {
	$atts = shortcode_atts(array(
		'invoice_id' => null,
	), $atts, $shortcode_name);
	
	$merge_tags = ah_get_invoice_merge_tags( "placeholders" );
	
	ob_start();
	?>
<table id="ah-merge-tags-preview">
	<tbody>
	<?php
	foreach( $merge_tags as $key => $value ) {
		?>
		<tr>
			<th style="text-align: left;"><?php echo esc_html( $key ); ?></th>
			<td><?php echo esc_html( $value ); ?></td>
		</tr>
		<?php
	}
	?>
	</tbody>
</table>
	<?php
	
	return trim(ob_get_clean());
}
add_shortcode( 'ah_invoice_merge_tags_preview', 'shortcode_ah_invoice_merge_tags_preview' );