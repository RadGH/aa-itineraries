<?php
get_header();

the_post();
?>
	<div class="container">
		<div class="content" data-aos="fade">
			
			<article <?php post_class( 'entry entry-single' ); ?>>
				
				<header class="entry-header">
					<div class="page-header container" >
						<?php the_title( '<h1 class="page-title">', '</h1>' ); ?>
					</div>
				</header>
			
			</article>
			
			<div class="account-columns">
				
				<div class="account-sidebar">
					<?php
					
					// see plugins/aa-itineraries/classes/menu.php
					do_action( 'ah_display_account_menu' );
					
					?>
				</div>
				
				<div class="account-content">
					<?php
					// Flex:
					// while( have_rows('flex_sections') ): the_row();
					// 	get_template_part( '_templates/flex-page-rows' );
					// endwhile;
					
					// Page:
					include( __DIR__ . '/content/content-invoice.php' );
					
					?>
				</div>
			
			</div>
		
		</div>
	</div>
<?php
get_footer();