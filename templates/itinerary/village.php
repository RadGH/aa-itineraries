<?php
// Village content
// Used when viewing an village, or when download a pdf

// These can be overwritten, see content/itinerary.php
if ( !isset($post_id) ) $post_id = get_the_ID();
if ( !isset($additional_content) ) $additional_content = false;

if ( !isset($first_bookmark) ) $first_bookmark = false;

$slug = get_post_field( 'post_name', $post_id );

$title = get_the_title( $post_id );
$subtitle = get_field( 'subtitle', $post_id );
$image_id = get_field( 'image', $post_id, false );
$intro = get_field( 'village_intro', $post_id );
$details = get_field( 'around_the_village', $post_id );

if ( $additional_content ) $details .= "\n\n" . $additional_content;
?>

<?php
if ( ah_is_pdf() ) {
	?>
	<htmlpagefooter name="footer_village_main_<?php echo $post_id; ?>" style="display:none">
		<table class="footer-table" width="100%"><tr>
				<td width="50%"><?php echo $title . ' &ndash; ' . $subtitle; ?></td>
				<td width="50%" align="right">{PAGENO}</td>
			</tr></table>
	</htmlpagefooter>
	
	<style>
		#village-main-<?php echo $post_id; ?> {
			page: village_main_<?php echo $post_id; ?>;
		}

		@page village_main_<?php echo $post_id; ?> {
			even-footer-name: footer_village_main_<?php echo $post_id; ?>;
			odd-footer-name: footer_village_main_<?php echo $post_id; ?>;
		}
	</style>
	<?php
}
?>

<section id="village-<?php echo esc_attr($slug); ?>" class="pdf-section village village-<?php echo esc_attr($slug); ?> village-id-<?php echo $post_id; ?>">
	
	<div class="pdf-page" id="village-main-<?php echo $post_id; ?>">
	
	<?php if ( $first_bookmark ) { ?>
		<?php ah_display_bookmark( 'Villages', 0 ); ?>
	<?php } ?>
		<?php ah_display_bookmark( $title, 1 ); ?>
	
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
