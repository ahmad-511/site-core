<?php
declare (strict_types = 1);

require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . '/../configs/global.php';

use App\Core\App;
use App\Core\Router;
use App\Core\Template;
use App\Core\Captcha;
use App\Core\DB;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Path;
use App\Core\Request;

session_name('ETESALATAK');
session_start();
date_default_timezone_set(DEFAULT_TIMEZONE);

DB::setDNS(DB_HOST, DB_NAME);
DB::setUser(DB_USER, DB_PASSWORD);
DB::setTimezone(DEFAULT_TIMEZONE);

Router::setHomePageCode('home');
Router::setLocales(ACCEPTED_LOCALES);
Router::setDefaultLocale(DEFAULT_LANGUAGE);
Router::setLocaleMapper([
    'login' => 'en',
    'dashboard' => 'en',
]);

Router::setViewsDir(__DIR__ .'/../views/');
Router::setAccessDeniedView('access-denied');
Router::setPageNotFoundView('page-not-found');
Router::setLayout('main');

Request::setLocales(ACCEPTED_LOCALES);

Path::setDefaultLocale(DEFAULT_LANGUAGE);

Template::setTemplatesDir(__DIR__ . '/../templates/');
Template::setDefaultLocale(DEFAULT_LANGUAGE);
Template::setGeneralParams([
    'WEBSITE_TITLE' => WEBSITE_TITLE,
    'WEBSITE_URL' => WEBSITE_URL,
    'SUPPORT_EMAIL' => SUPPORT_EMAIL,
]);

Captcha::$Font = __DIR__ . '/../fonts/DroidSerifBold.ttf';

Logger::setLogPath(__DIR__ .'/../logs/');

Mailer::setDebugMode(MAILER_DEBUG_MODE);

?>