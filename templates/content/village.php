<?php
// Village content
// Used when viewing an village, or when download a pdf

// These can be overwritten, see content/itinerary.php
if ( !isset($post_id) ) $post_id = get_the_ID();
if ( !isset($additional_content) ) $additional_content = false;

$slug = get_post_field( 'post_name', $post_id );

$title = get_the_title( $post_id );
$subtitle = get_field( 'subtitle', $post_id );
$image_id = get_field( 'image', $post_id, false );
$intro = get_field( 'village_intro', $post_id );
$details = get_field( 'around_the_village', $post_id );

if ( $additional_content ) $details .= "\n\n" . $additional_content;
?>

<section id="village-<?php echo esc_attr($slug); ?>" class="pdf-section village village-<?php echo esc_attr($slug); ?> village-id-<?php the_ID(); ?>">
	
	<div class="pdf-page" id="village-main-<?php the_ID(); ?>">
	
	<?php if ( $title || $subtitle ) { ?>
	<div class="section-heading village-heading">
		<?php if ( $title ) echo '<h1 class="pdf-title">', $title, '</h1>'; ?>
		
		<?php if ( $subtitle ) echo '<h2 class="pdf-subtitle">', $subtitle, '</h2>'; ?>
	</div>
	<?php } ?>
	
	<?php if ( $image_id ) { ?>
	<div class="section-image village-image">
		<?php ah_display_image( $image_id, 815, 360 ); ?>
	</div>
	<?php } ?>
	
	<?php if ( $intro ) { ?>
	<div class="section-content village-intro">
		<?php ah_display_content_columns( $intro ); ?>
	</div>
	<?php } ?>
	
	<?php if ( $details ) { ?>
	<div class="section-content around-the-village">
		<?php echo '<h2>In and Around ', $title, '</h2>'; ?>
		
		<?php ah_display_content_columns( $details ); ?>
	</div>
	<?php } ?>
	
	</div>
	
</section>
