<?php

// Apply page protection
AH_Hike()->protect_page();

AH_Theme()->load_template( __DIR__ . '/content/hike.php' );

return;