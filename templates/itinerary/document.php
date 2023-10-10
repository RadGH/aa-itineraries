<?php

// Unlike hikes and villages, this template is NOT used by a single document page. It is only used on the full itinerary page.
if ( !isset($title) ) $title = false;
if ( !isset($html_id) ) $html_id = false;
if ( !isset($image_id) ) $image_id = false;
if ( !isset($url) ) $url = false;
if ( !isset($text) ) $text = false;
if ( !isset($first_bookmark) ) $first_bookmark = false;
if ( !isset($bookmark_title) ) $bookmark_title = false;

$slug = 'document-' . $html_id;
?>

<?php
if ( ah_is_pdf() ) {
	?>
	<!-- Main -->
	<htmlpagefooter name="footer_document_<?php echo $slug; ?>" style="display:none">
		<table class="footer-table" width="100%"><tr>
				<td width="50%"><?php echo 'Document: ' . $title; ?></td>
				<td width="50%" align="right">{PAGENO}</td>
			</tr></table>
	</htmlpagefooter>
	
	<style>
		#page-document-<?php echo esc_attr($slug); ?> {
			page: document_<?php echo $slug; ?>;
		}

		@page document_<?php echo $slug; ?> {
			even-footer-name: footer_document_<?php echo $slug; ?>;
			odd-footer-name: footer_document_<?php echo $slug; ?>;
		}
	</style>
	<?php
}
?>

<!-- document: <?php echo $slug; ?> -->
<section id="<?php echo esc_attr($html_id); ?>" class="document document-<?php echo esc_attr($slug); ?>">
	
	<div class="pdf-page page-document" id="page-document-<?php echo esc_attr($slug); ?>">
		
		<?php if ( $first_bookmark ) { ?>
			<?php ah_display_bookmark( $bookmark_title, 0 ); ?>
		<?php } ?>
		
		<?php ah_display_bookmark( $title, 1 ); ?>
		
		<?php if ( $title ) { ?>
			<div class="section-heading document-heading">
				<?php if ( $first_bookmark && $bookmark_title ) { ?>
					<h1 class="pdf-title"><?php echo $bookmark_title; ?></h1>
				<?php } ?>
				
				<h2 class="document-title"><?php echo esc_html($title); ?></h2>
			</div>
		<?php } ?>
		
		<?php if ( $text ) { ?>
			<div class="section-content document-summary">
				<?php echo wpautop( $text ); ?>
			</div>
		<?php } ?>
		
		<?php if ( $image_id ) { ?>
			<div class="section-content document-image">
				<?php
				if ( $url ) echo '<a href="', esc_attr($url), '">';
				
				ah_display_image( $image_id );
				
				if ( $url ) echo '</a>';
				?>
			</div>
		<?php } ?>
	
	</div>

</section>