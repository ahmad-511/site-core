<?php

use App\Core\App;
use App\Core\Router;
use App\Core\Auth;
?>

<header>
    <a class="logo" href="/">
        <img src="img/logo-small.png" alt="Site logo">
    </a>

    <nav class="main-nav">        
        <div class="sections-menu">
            <a class="<?= App::setSelectedPage('home')?>" href="<?= Router::routeUrl('Home')?>"><?= App::loc('Home')?></a>
            <a class="<?= App::setSelectedPage('about')?>" href="<?= Router::routeUrl('About')?>"><?= App::loc('About')?></a>
            <a class="<?= App::setSelectedPage('contact-us')?>" href="<?= Router::routeUrl('Contact-Us')?>"><?= App::loc('Contact-Us')?></a>
            <?php foreach(Router::getLocales() as $l){
                    if($l == Router::getCurrentLocaleCode()){
                        continue;
                    }

                    echo '<a class="language" href="', Router::routeUrl(Router::getCurrentViewCode(), null, $l),'">', strtoupper($l), '</a>';
            }?>
            <?php if(Auth::isLoggedIn()):?>
                <a class="dashboard <?= App::setSelectedPage('dashboard')?>" href="<?= Router::routeUrl('Dashboard')?>"><?= App::loc('Dashboard')?></a>
            <?php endif;?>
        </div>
    </nav>
    <?php if(Auth::isLoggedIn()):?>
        <div class="logged-in-user">
            <span class="user-initials" tabindex="1"><?= Auth::getNameInitials(Auth::getUserName())?></span>
            <div class="user-menu">
                <p class="user-name"><?= Auth::getUserName()?></p>
                <p><button class="logout" id="logout">Logout</button></p>
            </div>
        </div>
        
        <script type="module">
            import {$, logout} from '/App/js/main.js';
            
            $('#logout').addEventListener('click', e => {
                logout('<?= Router::getCurrentLocaleCode() ?>');
            });
        </script>
    <?php endif ?>
</header>