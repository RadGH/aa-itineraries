<?php

// Apply page protection
AH_Hotel()->protect_page();

ah_add_theme_notice( 'info', 'You are previewing a hotel. On an itinerary, the hotel details are combined with the village and may appear differently.' );

AH_Theme()->load_template( __DIR__ . '/content/hotel.php' );

return;