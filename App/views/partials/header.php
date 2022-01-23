<?php

use App\Core\App;
use App\Core\Router;
?>

<header class="main-header">
    <a class="logo" href="<?= Router::routeUrl('home-view')?>">
        <img src="/App/img/logo.png" alt="Site logo">
        <span><?= App::loc(WEBSITE_TITLE)?></span>
    </a>
    
    <nav class="main-nav">
        <ul class="menu">
            <li><a tabindex="0"><i class="icon-globe"></i><span><?= App::loc('Language')?></span></a>
                <ul class="sub-menu language-menu">
                <?php foreach(Router::getLocales() as $l){
                    if($l == Router::getCurrentLocaleCode()){
                        continue;
                    }

                    echo '<li><a tabindex="0" class="language" href="', App::getURLforLanguage($l),'">', App::loc($l), '</a></li>';
                }?>
                </ul>
            </li>
            <li><a class="<?= App::setSelectedPage('notifications')?>" href="<?= Router::routeUrl('notifications-view')?>"><i class="icon-bell-o"></i><span><?= App::loc('Notifications')?></span></a><span class="notifications-counter hidden" id="notificationsCounter"></span></li>
            <li><a class="<?= App::setSelectedPage('offer-a-ride')?>" href="<?= Router::routeUrl('offer-a-ride-view')?>"><i class="icon-plus"></i><span><?= App::loc('Offer a ride')?></span></a></li>
            <li class="account"><a tabindex="0">
                <i class="icon-chevron-down"></i><img class="account-photo" src="<?= Router::routeUrl('account-photo', ['photo_path' => 0])?>"></a>
                <ul class="sub-menu account-menu">
                <?php if(App::isVerifiedUser()):?>
                        <li class="account-name"><?= App::getAccountName()?></li>
                        <li><a tabindex="0" href="<?= Router::routeUrl('my-profile-view')?>"><?= App::loc('My profile')?></a></li>
                        <li><a tabindex="0" class="logout" id="logout" href="#"><?= App::loc('Logout')?></a></li>
                    <?php else:?>
                        <li><a tabindex="0" href="<?= Router::routeUrl('login-view')?>"><?= App::loc('Login')?></a></li>
                        <li><a tabindex="0" href="<?= Router::routeUrl('sign-up-view')?>"><?= App::loc('Sign up')?></a></li>
                    <?php endif;?>
                </ul>
            </li>
        </ul>

        <?php if(App::isVerifiedUser()):?>
            <script type="module">
                import {$, logout, errorInResponse} from '/App/js/main.js';
                import xhr from '/App/js/xhr.js';
                
                let audio = null;

                // Activate logout operation
                $('#logout').addEventListener('click', e => {
                    e.preventDefault();
                    
                    logout('<?= Router::getCurrentLocaleCode() ?>');
                });

                // Wait for user interaction otherwise browser policy will stop audio playing
                document.body.addEventListener('click', function temp(){
                    audio = new Audio('/App/audio/correct-answer-tone.wav');
                    this.removeEventListener('click', temp);
                });

                const docTitle = document.title;
                let lastCount = 0;

                function checkNotification(){
                    xhr({
                        method: 'GET',
                        url: 'api/Notification/Count',
                        callback: resp => {
                            if (errorInResponse(resp, true)) {
                                setTimeout(checkNotification, 5000);
                                return false;
                            }

                            // Display notifications count
                            const notifsCounter = $('#notificationsCounter');

                            let count = resp.data;

                            if(count > 0){
                                notifsCounter.classList.remove('hidden');

                                // Get saved notification count
                                const sCount = localStorage.getItem('notifications')||0;

                                // Only play notification sound if not already played by other tab/window
                                if(count > lastCount && count > sCount){
                                    // New notification
                                    
                                    if(audio){
                                        audio.play();
                                    }
                                }

                                lastCount = count;
                            }else{
                                notifsCounter.classList.add('hidden');
                            }
                            
                            localStorage.setItem('notifications', count);

                            if(count > 99){
                                count = '99+';
                            }

                            notifsCounter.textContent = count;

                            if(count > 0){
                                document.title = `🔔${docTitle}`;
                            }else{
                                document.title = docTitle;
                            }

                            setTimeout(checkNotification, 5000);
                        }
                    });
                }

                // Activate notification count checker
                checkNotification();
            </script>
        <?php endif ?>
    </nav>
</header>