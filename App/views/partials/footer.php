<?php

use App\Core\App;
use App\Core\Router;

?>
<footer class="main-footer">
    <p class="copyright-design">
        <span class="copyright"><?= App::loc('All Rights Reserved')?> <?= COPYRIGHT?></span>
        <span class="comma">, </span>
        <span class="design"><?= App::loc('Designed & developed by')?> <a href="<?= DEVELOPER_URL?>" target="_blank"><?= DEVELOPER?></a></span>
    </p>

    <p class="website-latest-update"><?= App::loc('Latest update {date}', '', ['date' => '<span class="bidi">' . WEBSITE_LATEST_UPDATE . '</span>'])?></p>
</footer>

<div class="gdpr-consent hidden" id="gdprConsent">
    <?= App::loc('This website uses minimum amount of cookies to provide you with best experience')?>
    <button class="button"><?= App::loc('I Consent')?></button>
</div>

<script type="module">
    import {$} from '/App/js/main.js';

    if(localStorage.getItem('gdpr_consent') == 1){
        $('#gdprConsent').remove();
    }else{
        $('#gdprConsent').classList.remove('hidden');

        $('#gdprConsent .button').addEventListener('click', e => {
            localStorage.setItem('gdpr_consent', 1);
            $('#gdprConsent').remove();
        });
    }
</script>