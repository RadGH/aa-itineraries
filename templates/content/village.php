<?php
// Village content
// Used when viewing an village, or when download a pdf

$slug = get_post_field( 'post_name', get_the_ID() );
$title = get_the_title();
$subtitle = get_field( 'subtitle', get_the_ID() );
$image_id = get_field( 'image', get_the_ID(), false );
$intro = get_field( 'village_intro', get_the_ID() );
$details = get_field( 'around_the_village', get_the_ID() );
?>

<section id="village-<?php echo esc_attr($slug); ?>" class="pdf-section village village-<?php echo esc_attr($slug); ?> village-id-<?php the_ID(); ?>">
	
	<div class="pdf-page">
	
	<?php if ( $title || $subtitle ) { ?>
	<div class="section-heading village-heading">
		<?php if ( $title ) echo '<h1 class="pdf-title">', $title, '</h1>'; ?>
		
		<?php if ( $subtitle ) echo '<h2 class="pdf-subtitle">', $subtitle, '</h2>'; ?>
		
		<div class="clear"></div>
	</div>
	<?php } ?>
	
	<?php if ( $image_id ) { ?>
	<div class="section-image village-image">
		<?php ah_display_image( $image_id, 1055, 360 ); ?>
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
