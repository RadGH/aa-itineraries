<?php
if ( !isset($title) ) $title = get_the_title();

get_header();

the_post();
?>
<div class="container">
	<div class="content" data-aos="fade">
		
		<article <?php post_class( 'entry entry-single' ); ?>>
			
			<header class="entry-header">
				<div class="page-header container" >
					<h1><?php echo $title; ?></h1>
				</div>
			</header>
		
		</article>
		
		<?php do_action( 'ah_display_notices' ); ?>
		
		<div class="account-columns">
			
			<div class="account-sidebar">
				<?php
				
				// see plugins/aa-itineraries/classes/menu.php
				do_action( 'ah_display_account_menu' );
				
				?>
			</div>
			
			<div class="account-content">