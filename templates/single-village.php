<?php

// Apply page protection
AH_Village()->protect_page();

$village_id = get_the_ID();

// Viewing a village will not show hotel details, since they are not directly related
$hotel_id = false;

ah_add_theme_notice( 'info', 'You are previewing a village. On an itinerary, the hotel details will be combined with this village and may appear differently.' );

AH_Theme()->load_template( __DIR__ . '/itinerary/village.php', compact('hotel_id', 'village_id') );

return;