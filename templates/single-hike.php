<?php

// Apply page protection
AH_Hike()->protect_page();

ah_add_theme_notice( 'info', 'You are previewing a hike.' );

AH_Theme()->load_template( __DIR__ . '/itinerary/hike.php' );

return;