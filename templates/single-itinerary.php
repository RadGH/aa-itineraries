<?php

// Apply page protection
AH_Itinerary()->protect_page();

AH_Theme()->load_template( __DIR__ . '/content/itinerary.php' );

return;