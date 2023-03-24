<?php
// Village content
// Used when viewing an village, or when download a pdf

$title = get_the_title();
$subtitle = get_field( 'subtitle', get_the_ID() );
$image = get_field( 'image', get_the_ID(), false );
$content = get_field( 'content', get_the_ID() );
?>

<pagebreak page-selector="village" class="village village-id-<?php the_ID(); ?>">
	<?php
	
	if ( $title ) echo '<h1>', $title, '</h1>';
	if ( $subtitle ) echo '<h2>', $subtitle, '</h2>';
	if ( $image ) echo wp_get_attachment_image( $image, 'full' );
	if ( $content ) echo $content;
	
	?>
</pagebreak>
