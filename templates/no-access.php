<?php

nocache_headers();

$account_page_title = 'No Access';

/**
 * Replace the <title> tag with the error page name.
 *
 * @param array $title {
 *     The document title parts.
 *
 *     @type string $title   Title of the viewed page.
 *     @type string $page    Optional. Page number if paginated.
 *     @type string $tagline Optional. Site description when on home page.
 *     @type string $site    Optional. Site title when not on home page.
 * }
 */
add_filter( 'document_title_parts', function( $parts ) use ( $account_page_title ) {
	$parts['title'] = $account_page_title;
	$parts['page'] = '';
	$parts['tagline'] = '';
	$parts['site'] = get_bloginfo('name');
	return $parts;
} );

/**
 * Replaces the <title> tag, specifically for Yoast
 */
add_filter( 'wpseo_title', function( $title ) use ( $account_page_title ) {
	return $account_page_title . ' - ' . get_bloginfo('name');
});

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