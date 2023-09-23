<?php
use App\Core\Localizer as L;
use App\Core\Router;
?>

<section class="verification-result">
    <h2><?= L::loc('Mobile number verification')?></h2>
    <div>
        <form id="frmVerifyMobile" class="verify-my-mobile-form" novalidate>
            <div class="control-group">
                <label for="verification_code"><?= L::loc('Verification code')?></label>
                <span class="validity verification_code"></span>
                <div class="control-component">
                    <input type="text" id="verification_code" required>
                    <button type="submit" id="btnSubmit" class="btn btn-yellow"><?= L::loc('Verify')?></button>
                </div>
                <p class="hint"><?= L::loc('Sent via SMS to your mobile number')?></p>
            </div>
        </form>
    </div>
</section>

<script type="module">
    import {$, $$, errorInResponse, showMessage} from '/App/js/main.js';
    import xhr from '/App/js/xhr.js';
    import Validator from '/App/js/Validator.js';

    const btnSubmit = $('#btnSubmit');
    const validator = new Validator();

    const lang = '<?= Router::getCurrentLocaleCode()?>';

    $('#frmVerifyMobile').addEventListener('submit', operationHandler);

     // Setup validator
     validator.add($('#verification_code'), '<?= L::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.verification-code'));
 
    // Handle CRUD operations
    function operationHandler(e) {
        e.preventDefault();

        const btnId = e.currentTarget.id;
        
        btnSubmit.disabled = false;

        switch (btnId) {
            case 'frmVerifyMobile':
                validator.clear();

                if(!validator.validate()){
                    showMessage('<?= L::loc('Some data are missing or invalid')?>', 'warning');
                    return;
                }

                const data = {
                    verification_code: $('#verification_code').value
                }
                
                btnSubmit.disabled = true;

                sendRequest('POST', 'VerifyMyMobile', null , data);

                break;
        }
    }

    function sendRequest(method = 'GET', dbOper = '', uriParams = [], body = {}){
         // Join uri params
         let routeParams = '';
        
        if(uriParams instanceof Array && uriParams.length){
            routeParams = '/' + uriParams.join('/');
        }
        
        const url = `${lang}/api/Account/${dbOper}${routeParams}`;

        xhr({
            method,
            url,
            body,
            callback: resp => {
                btnSubmit.disabled = false;
                
                if (errorInResponse(resp)) {
                    return false;
                }

                if(resp.redirect){
                    setTimeout(function(){
                        document.location.href = resp.redirect;
                    }, 2000);
                }
            }
        });
    }
</script>