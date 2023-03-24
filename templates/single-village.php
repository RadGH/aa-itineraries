<?php

// Apply page protection
AH_Village()->protect_page();

get_header();

$title = get_the_title();
$subtitle = get_field( 'subtitle', get_the_ID() );
$image = get_field( 'image', get_the_ID(), false );
$content = get_field( 'content', get_the_ID() );

?>

<?php do_action( 'ah_display_notices' ); ?>

<div class="container">
	<div class="content" data-aos="fade">
		<article <?php post_class( 'entry entry-single village' ); ?>>
			<?php
			
			if ( $title ) echo '<h1>', $title, '</h1>';
			if ( $subtitle ) echo '<h2>', $subtitle, '</h2>';
			if ( $image ) echo wp_get_attachment_image( $image, 'full' );
			if ( $content ) echo '<div class="ah-content">', $content, '</div>';
			
			?>
		</article>
	</div>
</div>
<?php

get_footer();
