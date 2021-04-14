<?php
use App\Core\Router;
use App\Core\App;

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
    
    <?php App::includeMeta(Router::getCurrentViewCode());?>

    <link rel="canonical" href="<?= WEBSITE_URL, '/', Router::routeUrl(Router::getCurrentViewCode())?>">

    <?php
        foreach(Router::getLocales() as $l){
            echo '<link rel="alternate" hreflang="', $l, '" href="',WEBSITE_URL, '/', Router::routeUrl(Router::getCurrentViewCode(), null, $l), '">', "\n";
        }
    ?>

    <link rel="alternate" hreflang="x-default" href="<?= WEBSITE_URL, '/',  Router::routeUrl(Router::getCurrentViewCode())?>">

    <?php
        App::includeFiles(Router::getCurrentViewCode());
        
        if($pageDir == 'rtl'){
            echo '<link rel="stylesheet" href="App/css/style-rtl.css">'."\n";
        }
    ?>
</head>

<body class="page_<?= Router::getCurrentViewCode()?>">

<?php include __DIR__ . '/_header.php' ?>

<main>
    <?= $viewContent ?>
</main>

<?php include __DIR__ . '/_footer.php' ?>

<?php if(MAINTENANCE_MODE):?>
    <div class="maintenance-mode">
        <?= App::loc('Maintenance Mode')?>
    </div>
<?php endif?>
</body>
</html>