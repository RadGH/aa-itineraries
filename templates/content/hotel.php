<?php
// Hotel content
// In an itinerary, the hotel is displayed in combination with the village it is assigned to

$hotel_id = get_the_ID();

$slug = get_post_field( 'post_name', $hotel_id );

$hotel_name = get_field( 'hotel_name', $hotel_id );
$hotel_description = get_field( 'description', $hotel_id );
?>
<section id="hotel-<?php echo esc_attr($slug); ?>" class="pdf-section hotel hotel-<?php echo esc_attr($slug); ?> hotel-id-<?php echo $hotel_id; ?>">
	
	<div class="pdf-page" id="hotel-main-<?php echo $hotel_id; ?>">
	
		<?php if ( $hotel_name ) { ?>
		<div class="section-heading village-heading">
			<h1 class="pdf-title"><?php echo $hotel_name; ?></h1>
		</div>
		<?php } ?>
		
		<?php if ( $hotel_description ) { ?>
		<div class="section-content hotel-description">
			<?php ah_display_content_columns( $hotel_description ); ?>
		</div>
		<?php } ?>
	
	</div>
	
</section>
