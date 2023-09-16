<?php
declare (strict_types = 1);

use App\Core\App;
use App\Core\Localizer as L;
use App\Core\Router;

$pageDir = (Router::getCurrentLocaleCode() == 'ar')?'rtl':'ltr';
?>

<!DOCTYPE html>
<html lang="<?= Router::getCurrentLocaleCode()?>" dir="<?= $pageDir?>">
<head>
    <base href="<?= ROUTING_BASE?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
    <link rel="manifest" href="/icons/site.webmanifest">
    <link rel="mask-icon" href="/icons/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="/icons/favicon.ico">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="msapplication-config" content="/icons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">

    <meta name="robots" content="robots.txt">
    
    <?php App::includeMeta(Router::getCurrentFileName(true));?>

    <link rel="canonical" href="<?= WEBSITE_URL, Router::route(Router::getCurrentRouteName())?>">

    <?php
        foreach(Router::getLocales() as $l){
            echo '<link rel="alternate" hreflang="', $l, '" href="', WEBSITE_URL, Router::route(Router::getCurrentRouteName(), null, $l), '">', "\n";
        }
    ?>

    <link rel="alternate" hreflang="x-default" href="<?= WEBSITE_URL, Router::route(Router::getCurrentRouteName())?>">

    <?php
        App::includeFiles(Router::getCurrentFileName());
    ?>
</head>

<body class="<?= strtolower(Router::getCurrentLayout(true)), '-layout ', strtolower(Router::getCurrentFileName(true)), '-view'?>">

<main>
    <?= Router::getViewContent() ?>
</main>

<?php Router::renderContent('partials/footer') ?>
<?php Router::renderContent('partials/gdpr')?>

<?php if(MAINTENANCE_MODE):?>
    <div class="maintenance-mode">
        <?= L::loc('Maintenance Mode')?>
    </div>
<?php endif?>
<script type="module">
	import {markRequired, addShowPassword} from '/js/main.js';

	markRequired();
	addShowPassword();
</script>
</body>
</html>