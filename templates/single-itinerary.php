<?php

// Apply page protection
if ( is_singular( 'ah_itinerary_tpl') ) {
	// Template preview
	AH_Itinerary_Template()->protect_page();
}else{
	// Standard itinerary
	AH_Itinerary()->protect_page();
}

AH_Theme()->load_template( __DIR__ . '/itinerary/itinerary.php' );

return;