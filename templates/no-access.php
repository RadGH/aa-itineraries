<?php

nocache_headers();

$account_page_title = 'No Access';

include( __DIR__ . '/parts/account-header.php' );

if ( ! is_user_logged_in() ) {
	?>
	<p>You must be logged in to access this page.</p>
	<?php echo do_shortcode( '[ah_login_form]' ); ?>
	<?php
}else{
	ah_add_theme_notice( 'error', 'Your account does not have access to this item.' );
}

include( __DIR__ . '/parts/account-footer.php' );