<?php

if ( ! isset($account_page_title) ) {
	$account_page_title = false;
}

$account_sidebar_template = __DIR__ . '/sidebar-menus/itinerary-sidebar.php';
$account_menu_title = 'Navigation';

include( __DIR__ . '/account-header.php' );