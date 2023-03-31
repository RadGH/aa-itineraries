<?php

// These can be overwritten, see content/itinerary.php
if ( !isset($post_id) ) $post_id = get_the_ID();
if ( !isset($additional_content) ) $additional_content = false;

if ( !isset($first_bookmark) ) $first_bookmark = false;

$slug = get_post_field( 'post_name', $post_id );

$title = get_the_title( $post_id );
$summary = get_field( 'summary', $post_id );
$elevation_diagram = get_field( 'elevation_diagram', $post_id, false );
$topographic_map = get_field( 'topographic_map', $post_id, false );
$content = get_field( 'content', $post_id );

$topographic_map_image = wp_get_attachment_image_src( $topographic_map, 'full' );

if ( $additional_content ) $content .= "\n\n" . $additional_content;
?>

<?php
if ( ah_is_pdf() ) {
	?>
	<!-- Main -->
	<htmlpagefooter name="footer_hike_main_<?php echo $post_id; ?>" style="display:none">
		<table class="footer-table" width="100%"><tr>
				<td width="50%"><?php echo $title; ?></td>
				<td width="50%" align="right">{PAGENO}</td>
			</tr></table>
	</htmlpagefooter>
	
	<style>
		#page-hike-main-<?php echo esc_attr($slug); ?> {
			page: hike_main_<?php echo $post_id; ?>;
		}

		@page hike_main_<?php echo $post_id; ?> {
			even-footer-name: footer_hike_main_<?php echo $post_id; ?>;
			odd-footer-name: footer_hike_main_<?php echo $post_id; ?>;
		}
	</style>
	
	<!-- Topographic Map -->
	<?php /*
	<htmlpagefooter name="footer_hike_topo_<?php echo $post_id; ?>" style="display:none">
		<table class="footer-table inverted" width="100%"><tr>
				<td width="50%"></td>
				<td width="50%" align="right">{PAGENO}</td>
			</tr></table>
	</htmlpagefooter>
    */ ?>
	
	<style>
		#page-hike-topo-<?php echo esc_attr($slug); ?> {
			page: hike_topo_<?php echo $post_id; ?>;
		}

		@page hike_topo_<?php echo $post_id; ?> {
			/*even-footer-name: footer_hike_topo_*/<?php //echo $post_id; ?>/*;*/
			/*odd-footer-name: footer_hike_topo_*/<?php //echo $post_id; ?>/*;*/
			
			even-footer-name: none;
			odd-footer-name: none;
			
			background-image: url(<?php echo $topographic_map_image[0]; ?>);

			<?php
			// 815px x 1055px
			$size = ah_fit_image_size($topographic_map_image[1], $topographic_map_image[1], 815, 1055);
            ?>
			sheet-size: <?php echo $size[0] . 'px ' . $size[1] . 'px'; ?>;
		}
	</style>
	<?php
}
?>
<section id="hike-<?php echo esc_attr($slug); ?>" class="pdf-section hike hike-<?php echo esc_attr($slug); ?> hike-id-<?php echo $post_id; ?>">
	
	<div class="pdf-page page-hike" id="page-hike-main-<?php echo esc_attr($slug); ?>">
		
		<?php if ( $first_bookmark ) { ?>
			<?php ah_display_bookmark( 'Hikes', 0 ); ?>
		<?php } ?>
		
		<?php ah_display_bookmark( $title, 1 ); ?>
		
		<?php if ( $title ) { ?>
			<div class="section-heading hike-heading">
				<?php if ( $title ) echo '<h1 class="pdf-title">', $title, '</h1>'; ?>
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
		?>
		<div class="pdf-page hike-topographic-map-page" id="page-hike-topo-<?php echo esc_attr($slug); ?>">
			
			<?php if ( ! ah_is_pdf() || ah_is_pdf_preview() ) { ?>
				<div class="full-page-image">
					<?php ah_display_image( $topographic_map, 815, 1055 ); ?>
				</div>
			<?php } ?>
		
		</div>
		<?php
	}
	?>

</section>