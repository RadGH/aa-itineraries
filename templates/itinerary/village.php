<?php
// Village content
// Used when viewing an village, or when download a pdf

// These can be overwritten, see content/itinerary.php
if ( !isset($village_id) ) $village_id = get_the_ID();
if ( !isset($hotel_id) ) $hotel_id = false;
if ( !isset($additional_content) ) $additional_content = false;
if ( !isset($first_bookmark) ) $first_bookmark = false;

$slug = get_post_field( 'post_name', $village_id );

$title = get_the_title( $village_id );
$image_id = get_field( 'image', $village_id, false );
$intro = get_field( 'village_intro', $village_id );
$details = get_field( 'around_the_village', $village_id );

if ( AH_Hotel()->is_valid( $hotel_id ) ) {
	$hotel_name = get_field( 'hotel_name', $hotel_id );
	$hotel_description = get_field( 'description', $hotel_id );
}else{
	$hotel_name = false;
	$hotel_description = false;
}
?>

<?php
if ( ah_is_pdf() ) {
	?>
	<htmlpagefooter name="footer_village_main_<?php echo $village_id; ?>" style="display:none">
		<table class="footer-table" width="100%"><tr>
				<td width="50%"><?php echo $title; ?></td>
				<td width="50%" align="right">{PAGENO}</td>
			</tr></table>
	</htmlpagefooter>
	
	<style>
		#village-main-<?php echo $village_id; ?> {
			page: village_main_<?php echo $village_id; ?>;
		}

		@page village_main_<?php echo $village_id; ?> {
			even-footer-name: footer_village_main_<?php echo $village_id; ?>;
			odd-footer-name: footer_village_main_<?php echo $village_id; ?>;
		}
	</style>
	<?php
}
?>

<section id="village-<?php echo esc_attr($slug); ?>" class="pdf-section village village-<?php echo esc_attr($slug); ?> village-id-<?php echo $village_id; ?>">
	
	<div class="pdf-page" id="village-main-<?php echo $village_id; ?>">
	
	<?php if ( $first_bookmark ) { ?>
		<?php ah_display_bookmark( 'Villages', 0 ); ?>
	<?php } ?>
		<?php ah_display_bookmark( $title, 1 ); ?>
	
	<?php if ( $title || $hotel_name ) { ?>
	<div class="section-heading village-heading">
		<?php if ( $title ) echo '<h1 class="pdf-title">', $title, '</h1>'; ?>
		
		<?php if ( $hotel_name ) echo '<h2 class="pdf-subtitle">', $hotel_name, '</h2>'; ?>
	</div>
	<?php } ?>
	
	<?php if ( $image_id ) { ?>
	<div class="section-image village-image">
		<?php ah_display_image( $image_id, 815, 360 ); ?>
	</div>
	<?php } ?>
	
	<?php if ( $intro ) { ?>
	<div class="section-content village-intro">
		<?php echo wpautop( $intro ); ?>
	</div>
	<?php } ?>
	
	<?php if ( $hotel_description ) { ?>
	<div class="section-content hotel-description">
		<?php if ( $hotel_name ) echo '<h3>', $hotel_name, '</h3>'; ?>
		<?php echo wpautop( $hotel_description ); ?>
	</div>
	<?php } ?>
	
	<?php if ( $details ) { ?>
	<div class="section-content around-the-village">
		<?php echo '<h2>In and Around ', $title, '</h2>'; ?>
		
		<?php echo wpautop( $details ); ?>
		
		<?php
		if ( $additional_content ) {
			echo wpautop( $additional_content );
		}
		?>
		
	</div>
	<?php } ?>
	
	</div>
	
</section>
