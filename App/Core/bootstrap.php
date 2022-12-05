<?php
declare (strict_types = 1);

require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . '/../configs/global.php';

use App\Core\App;
use App\Core\Auth;
use App\Core\Router;
use App\Core\Template;
use App\Core\Captcha;
use App\Core\DB;
use App\Core\Logger;
use App\Core\Path;
use App\Core\Request;
use App\Service\MailService;

session_name(SESSION_NAME);
session_start();
date_default_timezone_set(DEFAULT_TIMEZONE);

Auth::$AuthSession = AUTHENTICATION_SESSION_NAME;
Auth::$AuthUserId = AUTHENTICATION_USER_ID;

DB::setDNS(DB_HOST, DB_NAME);
DB::setUser(DB_USER, DB_PASSWORD);
DB::setTimezone(DEFAULT_TIMEZONE);

Router::setAutoRouting(false);
Router::setHomePageCode('home');
Router::setLocales(ACCEPTED_LOCALES);
Router::setDefaultLocale(DEFAULT_LANGUAGE);
Router::setLocaleMapper([
    // 'login' => 'en',
    // 'dashboard' => 'en',
]);

Router::setViewsDir(BASE_DIR .'/views/');
Router::setAccessDeniedView('access-denied');
Router::setPageNotFoundView('page-not-found');
Router::setLayout('main');
Router::setCaseSensitivity(false);
// Include custom routes
require_once BASE_DIR . '/../custom-routes.php';

Request::setLocales(ACCEPTED_LOCALES);

Path::setDefaultLocale(DEFAULT_LANGUAGE);

Template::setTemplatesDir(BASE_DIR . '/templates/');
Template::setDefaultLocale(DEFAULT_LANGUAGE);
Template::setGeneralParams([
    '' => [
        'WEBSITE_URL' => WEBSITE_URL,
        'COPYRIGHT' => COPYRIGHT,
        'SUPPORT_EMAIL' => App::stripEmail(SUPPORT_EMAIL),
        'SUPPORT_MOBILE' => SUPPORT_MOBILE,
        'FACEBOOK_ID' => FACEBOOK_ID,
        'WHATSAPP' => WHATSAPP,
        'LINKEDIN_ID' => LINKEDIN_ID,
    ],
    'en' => [
        'WEBSITE_TITLE' => App::loc(WEBSITE_TITLE, 'en'),
        'WEBSITE_SLOGAN' =>  App::loc(WEBSITE_SLOGAN, 'en'),
        'COMPANY_ADDRESS' =>  App::loc(COMPANY_ADDRESS, 'en'),
        'UNSUBSCRIBE_URL' => WEBSITE_URL . '/EN/Unsubscribe',
    ],
    'ar' => [
        'WEBSITE_TITLE' => App::loc(WEBSITE_TITLE, 'ar'),
        'WEBSITE_SLOGAN' =>  App::loc(WEBSITE_SLOGAN, 'ar'),
        'COMPANY_ADDRESS' =>  App::loc(COMPANY_ADDRESS, 'ar'),
        'UNSUBSCRIBE_URL' => WEBSITE_URL . '/AR/Unsubscribe',
    ]
]);

Captcha::$Font = __DIR__ . '/../../fonts/DroidSerifBold.ttf';

MailService::SetMaxBatchSize(MAILER_MAX_BATCH_SIZE);
MailService::SetSendRate(MAILER_SEND_RATE);
MailService::SetSMTPAuth(MAILER_SMTP_AUTH);
MailService::SetSMTPSecure(MAILER_SMTP_SECURE);
MailService::SetOutputDir(MAILER_OUTPUT_DIR);
MailService::SetProviderDefault([
    'smtp_host' => MAILER_SMTP_HOST,
    'smtp_port' => MAILER_SMTP_PORT,
    'username' => MAILER_SMTP_USERNAME,
    'password' => MAILER_SMTP_PASSWORD,
    'send_from' => MAILER_SEND_FROM
]);

// SMSService::SetSMSClient(new FakeSMSService);

Logger::setLogPath(__DIR__ .'/../logs/');

//Mailer::setDebugMode(MAILER_DEBUG_MODE);

?>