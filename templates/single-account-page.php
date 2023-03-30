<?php

// Apply page protection
AH_Account_Page()->protect_page();

$account_page_title = get_the_title();

include( __DIR__ . '/parts/account-header.php' );

include( __DIR__ . '/content/account-page.php' );

include( __DIR__ . '/parts/account-footer.php' );