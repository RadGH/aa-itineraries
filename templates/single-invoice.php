<?php

// Apply page protection
AH_Invoice()->protect_page();

$account_page_title = 'Invoices';

include( __DIR__ . '/parts/account-header.php' );

include( __DIR__ . '/content/invoice.php' );

include( __DIR__ . '/parts/account-footer.php' );