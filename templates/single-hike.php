<?php
get_header();

$title = get_the_title();
$summary = get_field( 'summary', get_the_ID() );
$elevation_diagram = get_field( 'elevation_diagram', get_the_ID(), false );
$topographic_map = get_field( 'topographic_map', get_the_ID(), false );
$content = get_field( 'content', get_the_ID() );

?>
<div class="container">
	<div class="content" data-aos="fade">
		<article <?php post_class( 'entry entry-single hike' ); ?>>
			<?php
			
			if ( $title ) echo '<h1>', $title, '</h1>';
			if ( $summary ) echo '<div class="ah-summary">', $summary, '</div>';
			if ( $elevation_diagram ) echo wp_get_attachment_image( $elevation_diagram, 'full' );
			if ( $content ) echo '<div class="ah-content">', $content, '</div>';
			if ( $topographic_map ) echo wp_get_attachment_image( $topographic_map, 'full' );
			
			?>
		</article>
	</div>
</div>
<?php

get_footer();