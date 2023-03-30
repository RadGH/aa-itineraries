<?php
if ( !isset($account_page_title) ) {
	$account_page_title = get_the_title();
}

if ( !isset($account_menu_title) ) {
	// Passed to account-sidebar.php
	$account_menu_title = 'My Account';
}

if ( !isset($account_sidebar_template) ) {
	// Passed to account-sidebar.php
	$account_sidebar_template = __DIR__ . '/sidebar-menus/default-sidebar.php';
}

get_header();

the_post();
?>
<div class="container">
	<div class="content">
		
		<?php if ( $account_page_title ) { ?>
		<article <?php post_class( 'entry entry-single' ); ?>>
			
			<header class="entry-header">
				<div class="page-header container" >
					<h1><?php echo $account_page_title; ?></h1>
				</div>
			</header>
		
		</article>
		<?php } ?>
		
		<?php do_action( 'ah_display_notices' ); ?>
		
		<div class="account-columns">
			
			<div class="account-sidebar">
				<?php
				
				include( __DIR__ . '/account-sidebar.php' );
				
				?>
			</div>
			
			<div class="account-content">