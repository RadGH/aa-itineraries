<?php

$menu = AH_Account_Page()->get_active_menu();
if ( !$menu ) return;

$location_name = AH_Account_Page()->get_menu_location_name( $menu['unique_id'] );
if ( ! $location_name || ! has_nav_menu( $location_name ) ) return;

wp_nav_menu( array(
	'theme_location'  => $location_name,
	'menu_class'      => 'ah-account-menu',
	'container'       => false,
	// 'container_class' => 'ah-account-default-nav',
) );