<?php
error_reporting(E_ALL); // 0
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

ini_set('session.cookie_secure', '1');

define('DEVELOPER', 'Dev Team');
define('DEVELOPER_URL', 'https://zaksdg.com');

define('DEFAULT_TIMEZONE', 'Asia/Damascus');
define('ACCEPTED_LOCALES', ['ar', 'en']);
define('WEBSITE_TITLE', 'Site Core');
define('WEBSITE_SLOGAN', "Simple yet effective");
define('WEBSITE_URL', 'https://dev.site-core.com');
define('SUPPORT_EMAIL', 'Info <info@site-core.com>');
define('SUPPORT_MOBILE', '+9998887776');
define('WHATSAPP', '9998887776');
define('FACEBOOK_ID', 'site-core');
define('YOUTUBE_CHANNEL_ID', '');
define('LINKEDIN_ID', 'ahmadmurey');
define('COMPANY_ADDRESS', 'Somewhere');
define('COPYRIGHT', '&copy;' . date('Y') . ' <em>' . WEBSITE_TITLE . '</em>');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', 'ahmad_511');
define('DB_NAME', 'site_core');

define('ROUTING_BASE', '/');
define('DEFAULT_LANGUAGE', 'en'); // For default page display
define('MAIN_LANGUAGE', 'en'); // For main input fields
define('ALT_LANGUAGE', ''); // For alternative input fields
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_PASSWORD', '*****');

define('RECORDS_PER_PAGE', 25);

define('SESSION_NAME', 'site-core');
define('AUTHENTICATION_SESSION_NAME', 'user');
define('AUTHENTICATION_USER_ID', 'user_id');
define('ENABLE_REMEMBER_ME', true);
define('REMEMBER_ME_COOKIE_NAME', 'remember_me');
define('REMEMBER_ME_EXPIRE_DAYS', 30);

define('BASE_DIR', realpath(__DIR__ . '/../'));
define('UPLOAD_DIR', BASE_DIR . '/storage/');

define('MAILER_MAX_BATCH_SIZE', 50);
define('MAILER_SEND_RATE', 10); // per second
define('MAILER_SMTP_HOST', 'mail.uk2.net');
define('MAILER_SMTP_PORT', 587);
define('MAILER_SMTP_USERNAME', 'info@site-core.com');
define('MAILER_SMTP_PASSWORD', '123456S');
define('MAILER_SEND_FROM', 'Info<' . MAILER_SMTP_USERNAME . '>');
define('MAILER_SMTP_AUTH', false); // true
define('MAILER_SMTP_SECURE', ''); // tls, ssl, ''
define('MAILER_OUTPUT_DIR', BASE_DIR . '/logs/');
define('MAILER_DEBUG_MODE', false);

define('IMAGE_INVALIDATION_PERIOD', 60); // in seconds, user image url will be invalid after this period 
?>