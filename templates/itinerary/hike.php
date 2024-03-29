<?php

// These can be overwritten, see content/itinerary.php
if ( !isset($post_id) ) $post_id = get_the_ID();
if ( !isset($additional_content) ) $additional_content = false;
if ( !isset($first_bookmark) ) $first_bookmark = false;

$slug = get_post_field( 'post_name', $post_id );

// Data from itinerary.php. Not used on hike single page template.
if ( !isset($html_id) ) $html_id = 'hike-' . $slug;
if ( !isset($bookmark_title) ) $bookmark_title = 'Hikes';

$title = get_field( 'hike_name', $post_id ) ?: get_the_title( $post_id );
$summary = get_field( 'summary', $post_id );
$elevation_diagram = get_field( 'elevation_diagram', $post_id, false );
$topographic_map = get_field( 'topographic_map', $post_id, false );
$content = get_field( 'content', $post_id );

$links_title = get_field( 'link_title', $post_id );
$link_list = ah_get_links_list_html( get_field( 'link_links', $post_id ) );

// Topographic map image from WP media library
$t = $topographic_map ? wp_get_attachment_image_src( $topographic_map, 'full' ) : false;
$topo_image_src = $t[0] ?? false;
$topo_image_w = $t[1] ?? false;
$topo_image_h = $t[2] ?? false;

$size = ah_fit_image_size($topo_image_w, $topo_image_h, 815, 1055);
$topo_scaled_w = $size[0];
$topo_scaled_h = $size[1];

if ( $additional_content ) $content .= "\n\n" . $additional_content;
?>

<?php
if ( ah_is_pdf() ) {
	?>
	<!-- Main -->
	<htmlpagefooter name="footer_hike_main_<?php echo $post_id; ?>" style="display:none">
		<table class="footer-table" width="100%"><tr>
				<td width="50%"><?php echo 'Hike: '. $title; ?></td>
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
	
	<?php if ( $topographic_map ) { ?>
	<style>
		#page-hike-topo-<?php echo esc_attr($slug); ?> {
			page: hike_topo_<?php echo $post_id; ?>;
		}

		@page hike_topo_<?php echo $post_id; ?> {
			even-footer-name: none;
			odd-footer-name: none;

			margin: 0;
			
			background: url(<?php echo esc_html($topo_image_src); ?>) top left no-repeat;

			<?php
			// 815px x 1055px (ratio 0.7725118 or 1.294478)
			$ratio = $topo_image_w / $topo_image_h; // landscape 5 / 2 = 2.5; portrait 2 / 5 = 0.4
			$is_landscape = $ratio >= 1;
			
			// Check if landscape
			// resize 4 = scale to width (landscape), 5 = scale to height (portrait), 6 = scale both to fit
			if ( $is_landscape ) {
				?>
				size: landscape;
				background-image-resize: 4;
				<?php
			}else{
				?>
				size: portrait;
				background-image-resize: 5;
				<?php
			}
			?>
			
			
		}
	</style>
	<?php } ?>
	
	<?php
}
?>

<!-- hike: <?php echo $slug; ?> -->
<section id="<?php echo esc_attr($html_id); ?>" class="hike hike-<?php echo esc_attr($slug); ?> hike-id-<?php echo $post_id; ?>">
	
	<?php
	$show_hike_page = ( $summary || $link_list || $elevation_diagram || $content );
	if ( $show_hike_page ) {
	?>
	<div class="pdf-page page-hike" id="page-hike-main-<?php echo esc_attr($slug); ?>">
		
		<?php if ( $first_bookmark ) { ?>
			<?php ah_display_bookmark( $bookmark_title, 0 ); ?>
		<?php } ?>
		
		<?php ah_display_bookmark( $title, 1 ); ?>
		
		<?php if ( $title ) { ?>
			<div class="section-heading hike-heading">
				<?php if ( $title ) echo '<h1 class="pdf-title">', $title, '</h1>'; ?>
			</div>
		<?php } ?>
		
		<?php if ( $summary ) { ?>
			<div class="section-content hike-summary">
				<?php echo wpautop( $summary ); ?>
			</div>
		<?php } ?>
		
		<?php if ( $link_list ) { ?>
			<div class="section-content hike-links">
				<?php if ( $links_title ) echo '<h3>', $links_title, '</h3>'; ?>
				
				<ul class="link-list hike-link-list">
					<?php echo $link_list; ?>
				</ul>
			</div>
		<?php } ?>
		
		<?php if ( $elevation_diagram ) { ?>
			<div class="section-image hike-image hike-elevation-diagram">
				<?php ah_display_image( $elevation_diagram, 815, 360 ); ?>
			</div>
		<?php } ?>
		
		<?php if ( $content ) { ?>
			<div class="section-content hike-content">
				<?php echo wpautop( $content ); ?>
			</div>
		<?php } ?>
	
	</div>
	<?php } ?>
	
	<?php
	if ( $topographic_map ) {
		?>
		<div class="pdf-page hike-topographic-map-page" id="page-hike-topo-<?php echo esc_attr($slug); ?>">
			
			<?php if ( ! ah_is_pdf() || ah_is_pdf_preview() ) { ?>
				<div class="full-page-image">
					<?php ah_display_image( $topographic_map /*, 815, 1055 */ ); ?>
				</div>
			<?php } ?>
		
		</div>
		<?php
	}
	?>

</section>