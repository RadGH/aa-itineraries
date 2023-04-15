<?php

// Apply page protection
AH_Itinerary_Template()->protect_page();

ah_add_theme_notice( 'info', 'You are previewing an itinerary template.' );

include( __DIR__ . '/single-itinerary.php' );