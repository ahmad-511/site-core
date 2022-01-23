<?php

use App\Core\App;
use App\Core\Router;
?>

<section class="container">
    <form id="frmUnsubscribe" class="unsubscribe-form" novalidate>
        <h2 class="unsubscribe"><i class="icon-envelope-o"></i> <?= App::loc('Mailing list subscription')?></h2>

        <p class="description"><?= App::loc('Please choose what you want to subscribe to')?></p>
        
        <div class="control-group">
            <p>
                <input type="checkbox" id="notification_emails">
                <label for="notification_emails"><?= App::loc('Notification emails')?></label>
            </p>
        </div>

        <div class="form-operations">
            <button type="submit" class="btn btn-submit btn-green" id="btnSubmit"><?= App::loc('Update')?></button>
        </div>
    </form>
</section>

<script type="module">
    import {$, $$, errorInResponse, showMessage} from '/App/js/main.js';
    import xhr from '/App/js/xhr.js';

    const btnSubmit = $('#btnSubmit');

    const lang = '<?= Router::getCurrentLocaleCode()?>';
    
    $('#frmUnsubscribe').addEventListener('submit', e => {
        e.preventDefault();

        btnSubmit.disabled = true;

        const data = {
            notification_emails: $('#notification_emails').checked?1: 0
        }

        // Send xhr request
        xhr({
            method: 'POST',
            url: `${lang}/api/Account/Unsubscribe`,
            body: data,
            callback: resp => {
                btnSubmit.disabled = false;
                
                if (errorInResponse(resp)) {
                    return false;
                }
            }
        });
    });
</script>