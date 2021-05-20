<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

define('DEFAULT_TIMEZONE', 'Asia/Damascus');
define('ACCEPTED_LOCALES', ['en', 'ar']);
define('WEBSITE_TITLE', 'Site Core');
define('WEBSITE_URL', 'https://dev.site-core.com');
define('SUPPORT_EMAIL', 'Info <info@site-core.com>');
define('COPYRIGHT', 'Dev Team &copy;' . date('Y'));

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', '');

define('ROUTING_BASE', '/');
define('DEFAULT_LANGUAGE', 'en');
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_PASSWORD', '*****');

define('RECORDS_PER_PAGE', 25);
define('MAILER_DEBUG_MODE', true);

define('SESSION_NAME', 'sitecore');
define('AUTHENTICATION_SESSION_NAME', 'auth_sess');

// PayPal configs
define('PAYPAL_SANDBOX_MODE', true);
define('PAYPAL_CURRENCY', 'EUR');

if(PAYPAL_SANDBOX_MODE){
    define('LOCAL_CA_PATH',  __DIR__ . '/cert/cacert.pem'); //Curl local certificate path (useful for dev) or null to use server's certificate (in production)
    define('CLIENT_ID', '');
    define('CLIENT_SECRET', '');
}else{
    define('LOCAL_CA_PATH',  null);
    define('CLIENT_ID', '');
    define('CLIENT_SECRET', '');
}
?>