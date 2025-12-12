<?php
/**
 * MoMo Payment Gateway Configuration
 */

// Common Configuration
define('MOMO_PARTNER_CODE', 'MOMOBKUN20180529');
define('MOMO_ACCESS_KEY', 'klm05TvNBzhg7h7j');
define('MOMO_SECRET_KEY', 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa');

// API Endpoints (Sandbox)
define('MOMO_ENDPOINT_CREATE', 'https://test-payment.momo.vn/v2/gateway/api/create');
define('MOMO_ENDPOINT_QUERY', 'https://test-payment.momo.vn/v2/gateway/api/query');

// URLs for IPN and Return
// IMPORTANT: Replace 'localhost/shopthoitrang' with your actual domain
define('MOMO_RETURN_URL', 'http://localhost:90/shopthoitrang/includes/momo/result.php');
define('MOMO_IPN_URL', 'http://localhost:90/shopthoitrang/includes/momo/ipn_handler.php');

?>