<?php
get_header();
?>

<?php do_action( 'ah_display_notices' ); ?>

<div class="container">
	<div class="content" data-aos="fade">
		<article <?php post_class( 'entry entry-single ' . get_post_type() ); ?>>

			<div class="ah-page-view">
				
				<div style="float: right; position: relative; z-index: 2;">
					<a href="<?php echo get_permalink(); ?>/download/" target="download_<?php the_ID(); ?>" class="button">Download PDF</a>
				</div>