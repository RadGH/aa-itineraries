<?php
if ( !isset($account_menu_title) ) {
	$account_menu_title = 'My Account';
}

if ( !isset($account_sidebar_template) ) {
	$account_sidebar_template = __DIR__ . '/sidebar-menus/default-sidebar.php';
}
?>
<input type="checkbox" class="screen-reader-text" id="ah-mobile-nav-toggle">

<div class="ah-mobile-account-nav">
	
	<label id="ah-mobile-nav-label" for="ah-mobile-nav-toggle"><?php echo esc_html( $account_menu_title ); ?></label>
	
	<nav class="ah-account-menu-nav">
		<?php
		include( $account_sidebar_template );
		?>
	</nav>

</div>