<?php

$account_page_title = false;
$account_sidebar_template = __DIR__ . '/sidebar-menus/itinerary-sidebar.php';

switch( get_post_type() ) {
	
	case 'ah_hike':
		$account_menu_title = 'Hike';
		break;
		
	case 'ah_village':
		$account_menu_title = 'Village';
		break;
		
	case 'ah_itinerary_tpl':
	case 'ah_itinerary':
		$account_menu_title = 'Itinerary';
		break;
		
	default:
		$account_menu_title = 'Menu';
		break;
	
}

include( __DIR__ . '/account-header.php' );