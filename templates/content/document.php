<?php
global $post;

// -------------------------------------------
// This template is NOT used by the itinerary!
// See content/itinerary.php for that.

$document_url = ah_get_document_redirect_url( $post->ID );
$date = date('m/d/Y', strtotime( $post->post_date ) );
$image_id = ah_get_document_preview_image( $post->ID );

$button_text = 'Download';

// Custom URL can change the button text
if ( get_field( 'type', $post->ID ) == 'url' ) {
	if ( $v = get_field( 'button_text', $post->ID ) ) {
		$button_text = $v;
	}
}
?>
<article <?php post_class( 'entry entry-single document' ); ?>>
	
	<?php the_title( '<h3>', '</h3>' ); ?>
	
	<p><strong>Date Uploaded:</strong> <?php echo $date; ?></p>
	
	<?php if ( $image_id ) { ?>
	<p><a href="<?php echo esc_attr($document_url); ?>"><?php echo wp_get_attachment_image($image_id, 'document-preview'); ?></a></p>
	<?php } ?>
	
	<p><a href="<?php echo esc_attr($document_url); ?>" class="button button-primary"><?php echo esc_html($button_text); ?></a></p>

</article>