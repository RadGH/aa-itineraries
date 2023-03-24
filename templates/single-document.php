<?php

// Apply page protection
AH_Document()->protect_page();

$title = 'Documents';

include( __DIR__ . '/parts/account-header.php' );

include( __DIR__ . '/content/content-document.php' );

include( __DIR__ . '/parts/account-footer.php' );