<?php

$account_page_title = false;
$account_sidebar_template = __DIR__ . '/sidebar-menus/itinerary-sidebar.php';
$account_menu_title = 'Navigation';

/*
switch( get_post_type() ) {
	case 'ah_hike':
		break;
		
	case 'ah_village':
		$account_menu_title = 'Village';
		break;
		
	case 'ah_itinerary_tpl':
	case 'ah_itinerary':
		$account_menu_title = 'Itinerary';
		break;
}
*/

include( __DIR__ . '/account-header.php' );