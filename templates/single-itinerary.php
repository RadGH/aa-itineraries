<?php

// Apply page protection
if ( is_singular( 'ah_itinerary_tpl') ) {
	// Template preview
	AH_Itinerary_Template()->protect_page();
}else{
	// Standard itinerary
	AH_Itinerary()->protect_page();
}

// Get data and pages used on the itinerary
$itinerary_title = AH_Itinerary()->get_itinerary_title( get_the_ID() );
$itinerary_settings = AH_Itinerary()->get_itinerary_settings( get_the_ID() );

AH_Theme()->load_template( __DIR__ . '/itinerary/itinerary.php', array(
	'account_page_title' => $itinerary_title,
	'itinerary_settings' => $itinerary_settings,
) );

return;