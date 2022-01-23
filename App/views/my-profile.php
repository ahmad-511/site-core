<?php
    declare (strict_types = 1);

    use App\Core\App;
    use App\Core\Router;
    use App\Core\Auth;
?>

<section class="container save">
    <form id="frmMyProfile" class="my-profile-form" novalidate>
        <h2 class="my-profile"><i class="icon-user"></i> <?= App::loc('My profile')?></h2>
        
        <?php if(Auth::getUser('gender') == 'F'):?>
        <div class="control-group">
            <p>
                <input type="checkbox" id="hidden_personality" value="0">
                <label for="hidden_personality"><?= App::loc('Show my profile to females only')?></label>
            </p>
            <p class="hint">&nbsp;</p>
        </div>
        <?php endif; ?>

        <div class="control-set">
            <div class="control-group">
                <p>
                    <label for="name"><?= App::loc('Name')?></label>
                    <span class="validity name"></span>
                </p>
                <input type="text" id="name" pattern="[a-zA-Z ء-ي]{3,25}" required>
                <p class="hint">&nbsp;</p>
            </div>

            <div class="control-group">
                <p>
                    <label for="surname"><?= App::loc('Surname')?></label>
                    <span class="validity surname"></span>
                </p>
                <input type="text" id="surname" pattern="[a-zA-Z ء-ي]{3,25}" required>
                <p class="hint">&nbsp;</p>
            </div>
        </div>
    
        <div class="control-set">
            <div class="control-group">
                <p>
                    <label for="country"><?= App::loc('Country')?></label>
                    <span class="validity country"></span>
                </p>
                <select id="country" pattern="[A-Z]{2}" required></select>
                <p class="hint">&nbsp;</p>
            </div>

            <div class="control-group">
                <p>
                    <label for="mobile"><?= App::loc('Mobile')?></label>
                    <span class="validity mobile"></span>
                </p>
                <div class="mobile-number-container">
                    <span id="dialingCode" class="dialing-code" dir="ltr">+000</span>
                    <input type="tel" id="mobile" pattern="" required>
                </div>
                <p class="hint"><?= App::loc(MOBILE_VERIFICATION_MODE == 'Sned'?'Verification code will be sent to your mobile': 'You have to send an SMS from this number')?></p>
            </div>
        </div>

        <div class="control-set">
            <div class="control-group">
                <p>
                    <label for="email"><?= App::loc('Email')?></label>
                    <span class="validity email"></span>
                </p>
                <input type="email" id="email" required>
                <p class="hint"><?= App::loc('Used for customer support only')?></p>
            </div>

            <div class="control-group">
                <p>
                    <label for="password"><?= App::loc('Password')?></label>
                    <span class="validity password"></span>
                </p>
                <input type="password" id="password" pattern="(|.{6,})">
                <p class="hint"><?= App::loc('6 Characters minimum'), ', ',  App::loc('Leave it blank to keep it as is')?></p>
            </div>
        </div>

        <div class="control-set">
            <div class="control-group">
                <p>
                    <label for="preferred_language"><?= App::loc('Preferred language')?></label>
                    <span class="validity preferred_language"></span>
                </p>
                <select id="preferred_language"  data-default="ar">
                    <option value="ar"><?= App::loc('ar')?></option>
                    <option value="en"><?= App::loc('en')?></option>
                </select>
                <p class="hint">&nbsp;</p>
            </div>

            <div class="control-group">
                <p>&nbsp;</p>
                <p>
                    <input type="checkbox" id="notification_emails" value="0">
                    <label for="notification_emails"><?= App::loc('Send me notifications via email')?></label>
                </p>
                <p class="hint">&nbsp;</p>
            </div>
        </div>

        <div class="control-set">
            <div class="control-group personal-photo">
                <label for="personal_photo"><?= App::loc('Personal photo')?></label>
                <input type="file" id="personal_photo">
                <img src="/App/img/user.png" id="imgPersonalPhoto">
                <p class="hint"><?= App::loc('Clear photo of your face')?></p>
            </div>
            
            <div class="control-group">
                <table class="verification-status">
                    <tr>
                        <td><?= App::loc('Photo')?></td>
                        <td><span class="tag" id="photoVerification">&nbsp;</span></td>
                    </tr>
                    <tr>
                        <td><?= App::loc('Email')?></td>
                        <td>
                            <span class="tag" id="emailVerification">&nbsp;</span>
                            <button type="button" class="btn btn-yellow hidden" id="btnEmailVerification"><?= App::loc('Send link')?></button>
                        </td>
                    </tr>
                    <tr>
                        <td><?= App::loc('Mobile')?></td>
                        <td>
                            <span class="tag" id="mobileVerification">&nbsp;</span>
                            <?php if(MOBILE_VERIFICATION_MODE == 'Send'):?>
                                <button type="button" class="btn btn-yellow hidden" id="btnMobileVerification"><?= App::loc('Send SMS')?></button>
                                <p class="mobile-verification-link hidden" id="mobileVerificatonLink"><a href="<?= Router::routeUrl('verify-my-mobile-view')?>" target="_blank"><?= App::loc('Enter verification code')?></a></p>
                                <?php else:?>
                                    <p class="hint hidden" id="smsVerificationNumber"></p>
                            <?php endif?>
                        </td>
                    </tr>
                    <tr>
                        <td><?= App::loc('Status')?></td>
                        <td><span class="tag" id="status">&nbsp;</span></td>
                    </tr>
                </table>
                
                <em id="remarks"></em>
            </div>
        </div>

        <div class="control-group">
            <div id="ratingContainer" class="star-rating-container">
                <span class="rating-description" id="ratingDescription"><?= App::loc('Unspecified')?></span>
                <span class="rating" id="rating">0</span>
                <span class="ratings-count" id="ratingsCount">0</span>
            </div>
        </div>

        <div id="ratingDetailsContainer" class="rating-details"></div>

        <p class="more-links">
            <a target="_blank" href="<?= Router::routeUrl('profile-view', ['account_id' => Auth::getUser('account_id')])?>">■ <?= App::loc('View my public profile')?></a>
            <a target="_blank" href="<?= Router::routeUrl('account-ratings-view', ['account_id' => Auth::getUser('account_id')])?>">■ <?= App::loc('View my public ratings')?></a>
        </p>

        <div class="form-operations">
            <button type="button" class="btn btn-red" id="btnDeleteMyAccount"><?=App::loc('Delete my account')?></button>
            <button type="submit" class="btn btn-submit" id="btnSubmit"><?=App::loc('Save changes')?></button>
        </div>
    </form>
</section>

<script type="module">
    import {$, $$, errorInResponse, showMessage, generateListOptions} from '/App/js/main.js';
    import xhr from '/App/js/xhr.js';
    import Validator from '/App/js/Validator.js';
    import Prompt, {Action} from '/App/js/Prompt.js';
    import StartRating from '/App/js/StarRating.js';
    import Template from '/App/js/Template.js';

    const btnSubmit = $('#btnSubmit');
    const validator = new Validator();

    const lang = '<?= Router::getCurrentLocaleCode()?>';
    const accountStarRating = new StartRating($('#ratingContainer'), 'accountRating', 5, 0, '<i class="icon-star-o"></i>', '<i class="icon-star"></i>');
    const tplRatingDetails = new Template(`<?= App::load('/templates/{locale}/rating-details.html') ?>`);

    const ratingDescriptions = [
        '<?= App::loc('Unspecified')?>',
        '<?= App::loc('Bad')?>',
        '<?= App::loc('Okay')?>',
        '<?= App::loc('Good')?>',
        '<?= App::loc('Very good')?>',
        '<?= App::loc('Excellent')?>'
    ];

    const accountDelete = new Prompt(
        '<?= App::loc('This will permanently delete your account and its all related data')?>',
        [
            new Action('btnCancel', '<?= App::loc('Cancel')?>', false, 'btn'),
            new Action('btnDeleteMyAccount', '<?= App::loc('Delete my account')?>', true, 'btn btn-red')
        ]
    );

    accountDelete.events.listen('Action', action => {
        if(action.name != 'btnDeleteMyAccount'){
            return;
        }

        xhr({
            method: 'POST',
            url: `${lang}/api/Account/DeleteMyAccount`,
            callback: resp => {
                if (errorInResponse(resp)) {
                    return false;
                }

                setTimeout(function(){
                    document.location.href = resp.redirect || '<?= Router::routeUrl('home-view')?>';
                }, 100);
            }
        });
    });

    
    const promptEmailVerification = new Prompt(
        '',
        [
            new Action('btnCancel', '<?= App::loc('Cancel')?>', false, 'btn'),
            new Action('btnSend', '<?= App::loc('Send')?>', true, 'btn btn-green')
        ]
    );

    const promptMobileVerification = new Prompt(
        '',
        [
            new Action('btnCancel', '<?= App::loc('Cancel')?>', false, 'btn'),
            new Action('btnSend', '<?= App::loc('Send')?>', true, 'btn btn-green')
        ]
    );

    $('#frmMyProfile').addEventListener('submit', operationHandler);
    $('#btnDeleteMyAccount').addEventListener('click', operationHandler);
    $('#btnEmailVerification').addEventListener('click', operationHandler);
    <?php if(MOBILE_VERIFICATION_MODE == 'Send'):?>
        $('#btnMobileVerification').addEventListener('click', operationHandler);
    <?php endif?>

    // Setup validator
    validator.add($('#name'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.name'));
    validator.add($('#surname'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.surname'));
    validator.add($('#country'), '<?= App::loc('Invalid {field}', '', ['field' => ''])?>', $('.validity.country'));
    validator.add($('#mobile'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.mobile'));
    validator.add($('#email'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.email'));
    validator.add($('#password'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''], 1)?>', $('.validity.password'));

        // Sending email verification link confirmation
        promptEmailVerification.events.listen('Action', action => {
        if(action.name != 'btnSend'){
            return;
        }

        sendRequest(
            'POST',
            'SendMeVerificationEmail'
        );
    });

    // Sending mobile verification SMS confirmation
    promptMobileVerification.events.listen('Action', action => {
        if(action.name != 'btnSend'){
            return;
        }

        sendRequest(
            'POST',
            'SendMeVerificationSMS'
        );
    });

    // Load country public list
    xhr({
        method: 'GET',
        url: `${lang}/api/Country/PublicList`,
        callback: resp => {
            if (errorInResponse(resp)) {
                return false;
            }

            const selCountry = $('#country')
            generateListOptions(selCountry, resp.data, 'country_code', lang != '<?= ALT_LANGUAGE?>'? 'country': 'country_alt', '' ,['dialing_code', 'mobile_number_validator', 'plate_number_validator']);
            // Trggier the change event in order to update necessary items
            selCountry.dispatchEvent(new Event('change'));

            fillForm();
        }
    });

    // Update personal photo
    $('#imgPersonalPhoto').addEventListener('load', function(event){
        URL.revokeObjectURL(this.src);
    });

    $('#personal_photo').addEventListener('change', function(event){
        if(this.files.length){
            $('#imgPersonalPhoto').src = URL.createObjectURL(this.files[0]);
        }else{
            $('#imgPersonalPhoto').src = '/App/img/user.png';
        }
    });

    // Update country dialing code and mobile number validation rule
    function setCountryRules(data){
        $('#dialingCode').textContent = data.dialingCode;
        $('#mobile').pattern = data.mobileValidator;
    }

    $('#country').addEventListener('change', function(event){
        if(this.selectedOptions.length == 0){
            return;
        }

        setCountryRules({
            dialingCode: this.selectedOptions[0].dataset.dialing_code,
            mobileValidator: this.selectedOptions[0].dataset.mobile_number_validator
        });
    });

    // Handle CRUD operations
    function operationHandler(e) {
        e.preventDefault();

        const btnId = e.currentTarget.id;
        
        btnSubmit.disabled = false;

        switch (btnId) {
            case 'btnDeleteMyAccount':
                accountDelete.show();
                break;

            case 'btnEmailVerification':    
                promptEmailVerification.show();
                break;
            
            <?php if(MOBILE_VERIFICATION_MODE == 'Send'):?>
                case 'btnMobileVerification':
                    promptMobileVerification.show();
                    break;
            <?php endif?>

            case 'frmMyProfile':
                validator.clear();

                if(!validator.validate()){
                    showMessage('<?= App::loc('Some data are missing or invalid')?>', 'warning');
                    return;
                }

                // Using formData object in order to upload files
                const data = new FormData();
                
                <?php if(Auth::getUser('gender') == 'F'):?>
                    data.append('hidden_personality', $('#hidden_personality').checked?1:0);
                <?php endif; ?>
                data.append('name', $('#name').value);
                data.append('surname', $('#surname').value);
                data.append('country_code', $('#country').value);
                data.append('mobile', $('#mobile').value);
                data.append('email', $('#email').value);
                data.append('password', $('#password').value);
                data.append('preferred_language', $('#preferred_language').value);
                data.append('notification_emails', $('#notification_emails').checked?1:0);
                data.append('personal_photo', $('#personal_photo').files[0]||'');
                
                btnSubmit.disabled = true;

                sendRequest('POST', 'UpdateMyAccount', null , data);

                break;
        }
    }

    function fillForm(){
        xhr({
            method: 'GET',
            url: `${lang}/api/Account/ReadMyAccount`,
            callback: resp => {
                if (errorInResponse(resp)) {
                    return false;
                }

                const data = resp.data[0];

                <?php if(Auth::getUser('gender') == 'F'):?>
                    $('#hidden_personality').checked = !!parseInt(data['hidden_personality']);
                <?php endif; ?>

                $('#name').value = data['name'];
                $('#surname').value = data['surname'];
                $('#country').value = data['country_code'];
                $('#mobile').value = data['mobile'];
                $('#email').value = data['email'];
                $('#password').value = '';
                $('#preferred_language').value = data['preferred_language'];
                $('#notification_emails').checked = !!parseInt(data['notification_emails']);
                $('#personal_photo').value = '';
                $('#imgPersonalPhoto').src = '<?= Router::routeUrl('account-photo', ['photo_path' => 0])?>';
                $('#remarks').textContent = data['remarks'];
                
                setCountryRules({
                    dialingCode: data['dialing_code'],
                    mobileValidator: data['mobile_number_validator']
                });

                const photoVer = $('#photoVerification');
                const emailVer = $('#emailVerification');
                const mobileVer = $('#mobileVerification');
                const btnEmailVer = $('#btnEmailVerification');
                <?php if(MOBILE_VERIFICATION_MODE == 'Send'):?>
                    const btnMobileVer = $('#btnMobileVerification');
                <?php else:?>
                    const smsVerNumber = $('#smsVerificationNumber');
                <?php endif?>
                const status = $('#status');

                photoVer.classList.remove('tag-verified', 'tag-not-verified', 'tag-rejected');
                emailVer.classList.remove('tag-verified', 'tag-not-verified');
                mobileVer.classList.remove('tag-verified', 'tag-not-verified');
                status.classList.remove('tag-active', 'tag-not-active');

                if(data['personal_photo_verification'] == 'Verified'){
                    photoVer.textContent = '<?= App::loc('Verified')?>';
                    photoVer.classList.add('tag-verified');
                }else if(data['personal_photo_verification'] == 'Rejected'){
                    photoVer.textContent = '<?= App::loc('Rejected')?>';
                    photoVer.classList.add('tag-rejected');
                }else{
                    photoVer.textContent = '<?= App::loc('Not verified')?>';
                    photoVer.classList.add('tag-not-verified');
                }

                if(data['email_verification'] == 'Verified'){
                    emailVer.textContent = '<?= App::loc('Verified')?>';
                    emailVer.classList.add('tag-verified');
                    btnEmailVer.classList.add('hidden');
                }else{
                    emailVer.textContent = '<?= App::loc('Not verified')?>';
                    emailVer.classList.add('tag-not-verified');
                    btnEmailVer.classList.remove('hidden');
                }

                if(data['mobile_verification'] == 'Verified'){
                    mobileVer.textContent = '<?= App::loc('Verified')?>';
                    mobileVer.classList.add('tag-verified');
                    <?php if(MOBILE_VERIFICATION_MODE == 'Send'):?>
                        btnMobileVer.classList.add('hidden');
                        $('#mobileVerificatonLink').classList.add('hidden');
                    <?php else:?>
                        smsVerNumber.innerHTML = '';
                        smsVerNumber.classList.add('hidden');
                    <?php endif?>
                }else{
                    mobileVer.textContent = '<?= App::loc('Not verified')?>';
                    mobileVer.classList.add('tag-not-verified');
                    <?php if(MOBILE_VERIFICATION_MODE == 'Send'):?>
                        btnMobileVer.classList.remove('hidden');
                        $('#mobileVerificatonLink').classList.remove('hidden');
                    <?php else:?>
                        smsVerNumber.innerHTML = `<?= App::loc('Verify your mobile by sending the code {code} via SMS to this number {sms_number}', '', ['sms_number' => $params['sms_virtual_number']])?>`.replace('{code}', data['mobile_verification']);
                        smsVerNumber.classList.remove('hidden');
                    <?php endif?>
                }

                if(data['account_status'] == 'Pending'){
                    status.textContent = '<?= App::loc('Pending')?>';
                    status.classList.add('tag-pending');
                }else if(data['account_status'] == 'Verifying'){
                    status.textContent = '<?= App::loc('Verifying')?>';
                    status.classList.add('tag-verifying');
                }else if(data['account_status'] == 'Active'){
                    status.textContent = '<?= App::loc('Active')?>';
                    status.classList.add('tag-active');
                }else if(data['account_status'] == 'Deleted'){
                    status.textContent = '<?= App::loc('Deleted')?>';
                    status.classList.add('tag-deleted');
                }else{
                    status.textContent = '<?= App::loc('Suspended')?>';
                    status.classList.add('tag-suspended');
                }

                const email = data['email'];
                promptEmailVerification.setDescription(`<?= App::loc('Send email verification link to ${email}')?>`);

                const mobile =  data['mobile'];
                promptMobileVerification.setDescription(`<?= App::loc('Send mobile verification SMS to ${mobile}')?>`)

                accountStarRating.setValue(data['rating']);
                $('#rating').textContent = data['rating'];
                $('#ratingDescription').textContent = ratingDescriptions[parseInt(data['rating'])];
                $('#ratingsCount').textContent = ` / ${data['ratings_count']} <?= App::loc('Ratings')?>`;
                accountStarRating.freez();

                // Get rating details
                xhr({
                    method: 'GET',
                    url: `${lang}/api/Rating/GetMyRatingDetails`,
                    body: {},
                    callback: resp => {
                        if (errorInResponse(resp)) {
                            return false;
                        }

                        resp.data = resp.data.map( item => {
                            item['stars'] = '<i class="icon-star"></i>'.repeat(item['rating']) + '<i class="icon-star-o"></i>'.repeat(5 - item['rating']);
                            return item;
                        });

                        $('#ratingDetailsContainer').innerHTML = tplRatingDetails.render(resp.data);

                    }
                });
            }
        });
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