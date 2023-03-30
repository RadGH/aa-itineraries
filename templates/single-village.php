<?php

// Apply page protection
AH_Village()->protect_page();

AH_Theme()->load_template( __DIR__ . '/itinerary/village.php' );

return;