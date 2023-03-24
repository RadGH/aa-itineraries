<?php
get_header();
?>

<?php do_action( 'ah_display_notices' ); ?>

<div class="container">
	<div class="content" data-aos="fade">
		<article <?php post_class( 'entry entry-single ' . get_post_type() ); ?>>
