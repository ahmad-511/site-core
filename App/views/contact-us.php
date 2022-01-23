<?php

use App\Core\App;
use App\Core\Router;
?>

<section class="container">
    <form id="frmContact" class="contact-us-form" novalidate>
        <h2 class="contact-us"><i class="icon-envelope-o"></i> <?= App::loc('Contact us')?></h2>

        <p class="any-questions"><?= App::loc('Any questions')?></p>
        
        <div class="control-set">
            <div class="control-group">
                <p>
                    <label for="name"><?= App::loc('Name')?></label>
                    <span class="validity name"></span>
                </p>
                <input type="text" id="name" pattern="[a-zA-Z ء-ي]{3,50}" required>
            </div>

            <div class="control-group">
                <p>
                    <label for="email"><?= App::loc('Email')?></label>
                    <span class="validity email"></span>
                </p>
                <input type="email" id="email" required>
            </div>
        </div>

        <div class="control-group">
            <p>
                <label for="message"><?= App::loc('Message')?></label>
                <span class="validity message"></span>
            </p>
            <textarea id="message" rows="7" required></textarea>
        </div>

        <div class="control-set">
            <div class="control-group">
                <p>&nbsp;</p>
                <img src="<?= Router::routeUrl('captcha', ['captcha_name' => 'contact_us'])?>" id="captchaImage" alt="Captcha Image">
                <p class="hint"><?= App::loc('Click to refresh')?></span>
            </div>

            <div class="control-group">
                <p>
                    <label for="captcha_code"><?= App::loc('Captcha Code')?></label>
                    <span class="validity captcha_code"></span>
                </p>
                <input type="text" id="captcha_code" pattern="[a-zA-Z0-9]{4}" required>
                <span class="hint"><?= App::loc('Type in the code in the image')?></span>
            </div>
        </div>

        <div class="form-operations">
            <button type="submit" class="btn btn-submit btn-green" id="btnSubmit"><?= App::loc('Send')?></button>
        </div>
    </form>
</section>

<section class="container other-contacts">
    <h2><?= App::loc('Other methods to reach us')?></h2>

    <ul>
        <li>
            <label><?= App::loc('Email')?></label>
            <a href="mailto:<?= App::stripEmail(SUPPORT_EMAIL)?>"><bdo dir="ltr"><?= App::stripEmail(SUPPORT_EMAIL)?></bdo></a>
        </li>
        <li>
            <label><?= App::loc('Mobile')?></label>
            <a href="tel:<?= str_replace(' ', '', SUPPORT_MOBILE)?>"><bdo dir="ltr"><?= SUPPORT_MOBILE?></bdo></a>
        </li>
    </ul>
</section>

<script type="module">
    import {$, $$, resetForm, errorInResponse, showMessage} from '/App/js/main.js';
    import Validator from '/App/js/Validator.js';
    import xhr from '/App/js/xhr.js';

    const btnSubmit = $('#btnSubmit');
    const validator = new Validator();

    const lang = '<?= Router::getCurrentLocaleCode()?>';
    
    // Setup validator
    validator.add($('#name'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.name'));
    validator.add($('#email'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.email'));
    validator.add($('#message'), '<?= App::loc('{field} is required', '', ['field' => ''], 1)?>', $('.validity.message'));
    validator.add($('#captcha_code'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.captcha_code'));

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

        // Send xhr request
        xhr({
            method: 'POST',
            url: `${lang}/api/Contact/Support`,
            body: data,
            callback: resp => {
                btnSubmit.disabled = false;
                
                if (errorInResponse(resp)) {
                    return false;
                }

                resetForm($('#frmContact'));
            }
        });
    });

    $('#captchaImage').addEventListener('click', function (e) {
        this.src = '<?= Router::routeUrl('captcha', ['captcha_name' => 'contact_us'])?>';
    });
</script>