<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

define('DEVELOPER', 'ZAKS Developments Group');
define('DEVELOPER_URL', 'https://zaksdg.com');

define('DEFAULT_TIMEZONE', 'Asia/Damascus');
define('ACCEPTED_LOCALES', ['en', 'ar']);
define('WEBSITE_TITLE', 'Khedny M3ak');
define('WEBSITE_SLOGAN', "Let's take a ride");
define('WEBSITE_URL', 'https://dev.site-core.com');
define('SUPPORT_EMAIL', 'Info <info@site-core.com>');
define('SUPPORT_MOBILE', '+963 999 888 777');
define('WHATSAPP', '+963 999 888 777');
define('FACEBOOK_ID', 'khedny_m3ak');
define('COMPANY_ADDRESS', 'Germany - Hamburg');
define('COPYRIGHT', 'Dev Team &copy;' . date('Y'));
define('WEBSITE_LATEST_UPDATE', '2022-01-14');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', '');

define('ROUTING_BASE', '/');
define('DEFAULT_LANGUAGE', 'en'); // For default page display
define('MAIN_LANGUAGE', 'en'); // For main input fields
define('ALT_LANGUAGE', 'ar'); // For alternative input fields
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_PASSWORD', '*****');

define('RECORDS_PER_PAGE', 25);

define('SESSION_NAME', 'sitecore');
define('AUTHENTICATION_SESSION_NAME', 'auth_sess');
define('AUTHENTICATION_USER_ID', 'account_id');

define('BASE_DIR', realpath(__DIR__ . '/../'));
define('CACHE_DIR', BASE_DIR . '/cache/');
define('UPLOAD_DIR', BASE_DIR . '/uploads/');

define('MAILER_MAX_BATCH_SIZE', 50);
define('MAILER_SMTP_AUTH', false);
define('MAILER_SMTP_SECURE', ''); // tls, ssl, ''
define('MAILER_OUTPUT_DIR', BASE_DIR . '/logs/');
define('MAILER_DEBUG_MODE', false);

define('NOTIFICATION_CLEANUP_PERIOD', 30); // in days
define('IMAGE_INVALIDATION_PERIOD', 60); // in seconds, if user image url will be invalid after this period 

// Mobile verification mode
define('MOBILE_VERIFICATION_MODE', 'Send'); // Send, Receive

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