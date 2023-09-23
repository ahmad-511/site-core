<?php
    use App\Core\Localizer as L;
    use App\Core\Router;
?>

<section class="container center-center login">
    <form id="frmLogin" class="form login" novalidate>
        <h2>
            <img class="logo" src="/App/img/logo.png" alt="<?= L::loc(WEBSITE_TITLE)?>">
            <span><?= L::loc(WEBSITE_TITLE)?></span>
        </h2>

        <div class="control-group">
            <label for="email_mobile"><?= L::loc('Email or mobile number')?></label>
            <input type="text" id="email_mobile" dir="auto" required autocomplete="off" autofocus>
            <p class="validity email_mobile"></p>
        </div>
        <div class="control-group">
            <label for="password"><?= L::loc('Password')?></label>
            <input type="password" id="password" dir="auto" required autocomplete="new-password">
            <p class="validity password"></p>
        </div>
    
        <div class="form-operations">
            <button type="submit" class="btn btn-submit" id="btnSubmit"><?=L::loc('Login')?></button>
            <button type="button" class="btn" id="btnCancel"><?=L::loc('Cancel')?></button>
        </div>
    </form>
</section>

<script type="module">
    import {$, $$, errorInResponse, showMessage} from '/App/js/main.js';
    import xhr from '/App/js/xhr.js';
    import Validator from '/App/js/Validator.js';

    const btnSubmit = $('#btnSubmit');
    const btnCancel = $('#btnCancel');
    const validator = new Validator();
    
    const lang = '<?= Router::getCurrentLocaleCode()?>';
    
    $('#frmLogin').addEventListener('submit', operationHandler);
    btnCancel.addEventListener('click', operationHandler);

    // Setup validator
    validator.add($('#email_mobile'), '<?= L::loc('Invalid {field}', '', ['field' => 'Email or mobile number'])?>', $('.validity.email_mobile'));
    validator.add($('#password'), '<?= L::loc('Invalid {field}', '', ['field' => 'Password'], 1)?>', $('.validity.password'));

    // Handle CRUD operations
    function operationHandler(e) {
        e.preventDefault();

        const btnId = e.currentTarget.id;
        
        btnSubmit.disabled = false;
        
        switch (btnId) {
            case 'btnCancel':
                document.location.href = '<?= Router::route('home-view')?>';
                break;

            case 'frmLogin':
                if(!validator.validate()){
                    showMessage('<?= L::loc('Some data are missing or invalid')?>', 'warning');
                    return;
                }

                let data = {
                    email_mobile: $('#email_mobile').value,
                    password: $('#password').value
                };
                
                btnSubmit.disabled = true;
                // Send xhr request
                xhr({
                    method: 'POST',
                    url: `${lang}/api/Account/Login`,
                    body: data,
                    callback: resp => {
                        btnSubmit.disabled = false;
                        
                        if (errorInResponse(resp)) {
                            return false;
                        }

                        setTimeout(function(){
                            document.location.href = resp.redirect || '<?= Router::route('home-view')?>';
                        }, 2000);
                    }
                });

                break;
        }
    }

    const guargMsg = "<?= L::loc($params['GUARD_MESSAGE']) ?>";
    
    if(guargMsg){
        showMessage(guargMsg, 'error', 10000);
    }
</script>