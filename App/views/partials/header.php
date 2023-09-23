<?php

use App\Core\App;
use App\Core\Router;
use App\Core\Localizer as L;
?>

<header class="main-header">
    <a class="logo" href="<?= Router::route('home-view')?>">
        <img src="/img/logo.png" alt="Site logo">
        <span><?= L::loc(WEBSITE_TITLE)?></span>
    </a>
    
    <nav class="main-nav">
        <ul class="menu">
            <li><a class="<?= App::setSelectedPage('about')?>" href="<?= Router::route('about-view')?>"><i class="icon-bell-o"></i><span><?= L::loc('About')?></span></a></li>
            <li><a class="<?= App::setSelectedPage('contacts')?>" href="<?= Router::route('contacts-view')?>"><i class="icon-plus"></i><span><?= L::loc('Contacts')?></span></a></li>
            <li class="account"><a tabindex="0">
                <i class="icon-chevron-down"></i><img class="account-photo" src="<?= Router::route('account-photo', ['photo_path' => 0])?>"></a>
                <ul class="sub-menu account-menu">
                <?php if(App::isActiveUser()):?>
                        <li class="account-name"><?= App::getAccountName()?></li>
                        <li><a tabindex="0" href="<?= Router::route('my-profile-view')?>"><?= L::loc('My profile')?></a></li>
                        <li><a tabindex="0" class="logout" id="logout" href="#"><?= L::loc('Logout')?></a></li>
                    <?php else:?>
                        <li><a tabindex="0" href="<?= Router::route('login-view')?>"><?= L::loc('Login')?></a></li>
                        <li><a tabindex="0" href="<?= Router::route('sign-up-view')?>"><?= L::loc('Sign up')?></a></li>
                    <?php endif;?>
                </ul>
            </li>
        </ul>

        <?php if(App::isActiveUser()):?>
            <script type="module">
                import {$, logout, errorInResponse} from '/js/main.js';
                import xhr from '/js/xhr.js';
                import Prompt, {Action} from '/js/Prompt.js';

                let audio = null;

                const promptLogout = new Prompt(
                    '<?= L::loc('Logout from')?>',
                    [
                        new Action('btnThisDevice', '<?= L::loc('This device')?>', true, 'btn btn-orange'),
                        new Action('btnAllDevices', '<?= L::loc('All devices')?>', true, 'btn btn-red')
                    ]
                );

                // Sending mobile verification SMS confirmation
                promptLogout.events.listen('Action', action => {

                    switch(action.name){
                        case 'btnThisDevice':
                            logout(0, '<?= Router::getCurrentLocaleCode() ?>');
                            break;
                            
                        case 'btnAllDevices':
                            logout(1, '<?= Router::getCurrentLocaleCode() ?>');
                            break;
                    }
                });
                        
                // Activate logout operation
                $('#logout').addEventListener('click', e => {
                    e.preventDefault();
                    
                    <?php if(ENABLE_REMEMBER_ME):?>
                        promptLogout.show();
                    <?php else:?>
                        logout(1 , '<?= Router::getCurrentLocaleCode() ?>');
                    <?php endif?>
                });
            </script>
        <?php endif ?>
    </nav>
</header>