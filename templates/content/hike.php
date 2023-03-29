<?php

// These can be overwritten, see content/itinerary.php
if ( !isset($post_id) ) $post_id = get_the_ID();
if ( !isset($additional_content) ) $additional_content = false;

$slug = get_post_field( 'post_name', $post_id );

$title = get_the_title( $post_id );
$summary = get_field( 'summary', $post_id );
$elevation_diagram = get_field( 'elevation_diagram', $post_id, false );
$topographic_map = get_field( 'topographic_map', $post_id, false );
$content = get_field( 'content', $post_id );

if ( $additional_content ) $content .= "\n\n" . $additional_content;
?>
<section id="hike-<?php echo esc_attr($slug); ?>" class="pdf-section hike hike-<?php echo esc_attr($slug); ?> hike-id-<?php the_ID(); ?>">
	
	<div class="pdf-page page-hike" id="page-hike-<?php echo esc_attr($slug); ?>">
		
		<?php if ( $title ) { ?>
			<div class="section-heading hike-heading">
				<?php if ( $title ) echo '<h1 class="pdf-title">', $title, '</h1>'; ?>
				<div class="clear"></div>
			</div>
		<?php } ?>
		
		<?php if ( $summary ) { ?>
			<div class="section-content hike-summary">
				<?php ah_display_content_columns( $summary ); ?>
			</div>
		<?php } ?>
		
		<?php if ( $elevation_diagram ) { ?>
			<div class="section-image hike-image hike-elevation-diagram">
				<?php ah_display_image( $elevation_diagram, 815, 360 ); ?>
			</div>
		<?php } ?>
		
		<?php if ( $content ) { ?>
			<div class="section-content hike-content">
				<?php ah_display_content_columns( $content ); ?>
			</div>
		<?php } ?>
	
	</div>
	
	<?php
	if ( $topographic_map ) {
		$topo_image = wp_get_attachment_image_src( $topographic_map, 'full' );
		?>
		<div class="pdf-page hike-topographic-map-page" id="page-hike-topo-<?php echo esc_attr($slug); ?>">
			
			<div class="full-page-image">
				<?php ah_display_image( $topographic_map, 810, 1040 ); ?>
			</div>
			
		</div>
		<?php
	}
	?>

</section>
