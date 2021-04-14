<?php

use App\Core\App;
use App\Core\Router;
?>

<section class="contact-us">
    <h1><?= App::loc('Any Questions?')?></h1>

    <form id="frmContact" novalidate>
        <p class="control-group">
            <label for="name"><?= App::loc('Name')?></label>
            <span class="validity name"></span>
            <input type="text" id="name" required>
        </p>
        <p class="control-group">
            <label for="email"><?= App::loc('Email Address')?></label>
            <span class="validity email"></span>
            <input type="email" id="email" required>
        </p>

        <p class="control-group">
            <label for="message"><?= App::loc('Message')?></label>
            <span class="validity message"></span>
            <textarea id="message" rows="7" required></textarea>
        </p>

        <div class="control-set">
            <p class="control-group captcha">
                <img src="/api/CaptchaCode/Get" id="captchaImage" alt="Captcha Image">
                <span class="hint"><?= App::loc('Click to refresh')?></span>
            </p>
            <p class="control-group">
                <label for="captcha_code"><?= App::loc('Captcha Code')?></label>
                <span class="validity captcha_code"></span>
                <input type="text" id="captcha_code" required>
                <span class="hint"><?= App::loc('Type in the code in the image')?></span>
            </p>
        </div>

        <p class="form-operations">
            <input type="submit" class="button action" id="btnSubmit" value="<?= App::loc('Send')?>">
        </p>
    </form>
    
    <div class="other-contacts">
        <h3><?= App::loc('Other methods to reach us')?></h3>

        <ul>
            <li>
                <label><?= App::loc('Email')?></label>
                <a href="mailto:<?= str_replace(' ', '', SUPPORT_EMAIL)?>"><bdo dir="ltr"><?= SUPPORT_EMAIL?></bdo></a>
            </li>
        </ul>
    </div>
</section>
<script type="module">
    import {$, $$, resetForm, errorInResponse, showMessage} from '/App/js/main.js';
    import Validator from '/App/js/Validator.js';
    import Ajax from '/App/js/ajax.js';

    const btnSubmit = $('#btnSubmit');
    const validator = new Validator();

    // Setup validator
    validator.add($('#name'), '<?= App::loc('Type in your name')?>', $('.validity.name'));
    validator.add($('#email'), '<?= App::loc('Type in your email address')?>', $('.validity.email'));
    validator.add($('#message'), '<?= App::loc('Type in your message')?>', $('.validity.message'));
    validator.add($('#captcha_code'), '<?= App::loc('Type in your the code in the image')?>', $('.validity.captcha_code'));

    $('#frmContact').addEventListener('submit', e => {
        e.preventDefault();

        if(!validator.validate()){
            showMessage('<?= App::loc('Some data are missing or invalid')?>', 'warning');
            return;
        }

        btnSubmit.disabled = true;

        const data = {
            name: $('#name').value,
            email: $('#email').value,
            message: $('#message').value,
            captcha_code: $('#captcha_code').value
        }

        // Send Ajax request
        Ajax('POST', '/<?= Router::getCurrentLocaleCode()?>/api/Contact/Support',
            data,
            function (resp) {
                btnSubmit.disabled = false;
                
                if (errorInResponse(resp)) {
                    return false;
                }

                resetForm($('#frmContact'));
            }
        );
    });

    $('#captchaImage').addEventListener('click', function (e) {
        this.src = '/api/CaptchaCode/Get';
    });
</script>