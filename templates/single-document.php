<?php

// Apply page protection
AH_Document()->protect_page();

$account_page_title = 'Documents';

include( __DIR__ . '/parts/account-header.php' );

include( __DIR__ . '/content/document.php' );

include( __DIR__ . '/parts/account-footer.php' );