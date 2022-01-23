<?php
    use App\Core\App;
use App\Core\Router;

?>

<section class="center-center">
    <form id="frmSignup" class="container signup-form signup" novalidate>
        <h2><?= App::loc('Sign up to get benefit')?></h2>
       
        <div class="control-set">
            <div class="control-group">
                <p>
                    <label for="gender"><?= App::loc('Gender')?></label>
                    <span class="validity gender"></span>
                </p>
                <select id="gender" required autofocus>
                    <option value="M"><?= App::loc('M')?></option>
                    <option value="F"><?= App::loc('F')?></option>
                </select>
                <p class="hint">&nbsp;</p>
            </div>

            <div class="control-group hidden" id="hiddenPersonalityOption">
                <p>&nbsp;</p>
                <p>
                    <input type="checkbox" id="hidden_personality" value="0">
                    <label for="hidden_personality"><?= App::loc('Show my profile to females only')?></label>
                </p>
                <p class="hint"><?= App::loc('You can change this later')?></p>
            </div>
        </div>

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
                <p class="hint">
                    <?= App::loc(MOBILE_VERIFICATION_MODE == 'Send'?'Verification code will be sent to your mobile': 'You have to send an SMS from this number')?>
                    <?php if(MOBILE_VERIFICATION_MODE == 'Receive'):?>,
                        <a href="<?= Router::routeUrl('how-it-works-section-view', ['section' => 'Mobile-Verification'])?>" target="_blank"><?= App::loc('Why?')?></a>
                    <?php endif?>
                </p>
            </div>
        </div>

        <div class="control-set">
            <div class="control-group">
                <p>
                    <label for="email"><?= App::loc('Email')?></label>
                    <span class="validity email"></span>
                </p>
                <input type="email" id="email" required>
                <p class="hint">&nbsp;</p>
            </div>

            <div class="control-group">
                <p>
                    <label for="password"><?= App::loc('Password')?></label>
                    <span class="validity password"></span>
                </p>
                <input type="password" id="password" minlength="6" required>
                <p class="hint"><?= App::loc('6 Characters minimum')?></p>
            </div>
        </div>

        <div class="control-set">
            <div>

            </div>
            <div class="control-group personal-photo">
                <label for="personal_photo"><?= App::loc('Personal photo')?></label>
                <input type="file" id="personal_photo">
                <img src="/App/img/user.png" id="imgPersonalPhoto">
                <p class="hint"><?= App::loc('Clear photo of your face')?></p>
            </div>
        </div>

        <div class="toggle-car-registration">
            <input type="checkbox" id="chkCarRegistration">
            <label for="chkCarRegistration"><?= App::loc('Register your car now')?></label>
        </div>

        <div id="carRegistration" class="car-registration hidden">
            <div class="control-set">
                <div class="control-group">
                    <p>
                        <label for="plate_number"><?= App::loc('Plate number')?></label>
                        <span class="validity plate_number"></span>
                    </p>
                    <div class="plate-number-bakcground" id="plateNumberBackground">
                        <input type="text" id="plate_number" dir="ltr" pattern="" required>
                        <input type="text" id="plate_number2" dir="ltr" pattern="">
                    </div>
                    <p class="hint">&nbsp;</p>
                </div>

                <div class="control-group">
                    <p>
                        <label for="maker"><?= App::loc('Maker')?></label>
                        <span class="validity maker"></span>
                    </p>
                    <select id="maker" pattern="\d+" required></select>
                    <p class="hint"><a href="<?= Router::routeUrl('contact-us-view')?>"><?= App::loc('Please inform us in case you could not find your car maker')?></a></p>
                </div>
            </div>

            <div class="control-set">
                <div class="control-group">
                    <p>
                        <label for="model"><?= App::loc('Model')?></label>
                        <span class="validity model"></span>
                    </p>
                    <input type="text" id="model" required>
                    <p class="hint">&nbsp;</p>
                </div>

                <div class="control-group">
                    <p>
                        <label for="color"><?= App::loc('Color')?></label>
                        <span class="validity color"></span>
                    </p>
                    <input type="text" id="color" minlength="3" required>
                    <p class="hint">&nbsp;</p>
                </div>
            </div>
            
            <div class="control-set">
                <div class="control-group">
                    <p>
                        <label for="seats_number"><?= App::loc('Seats')?></label>
                        <span class="validity seats_number"></span>
                    </p>
                    <input type="number" id="seats_number" value="1" min="1" max="5">
                    <p class="hint"><?= App::loc('Number of seats allowed for passengers')?></p>
                </div>

                <div class="control-group car-photo">
                    <label for="car_photo"><?= App::loc('Car photo')?></label>
                    <input type="file" id="car_photo">
                    <img src="/App/img/car.png" id="imgCarPhoto">
                    <p class="hint"><?= App::loc('Clear front photo of car')?></p>
                </div>
            </div>
        </div>

        <div class="sign-up-agreement">
            <h3><?= App::loc('I agree to commit')?></h3>
            <p>
                <input type="checkbox" id="chkTOSPP">
                <label for="chkTOSPP"><?= App::loc('To {TOS} and {PP}', '', [
                    'TOS' => '<a target="_blank" href="'. Router::routeUrl('terms-of-service-view').'">'. App::loc('Terms of service').'</a>',
                    'PP' => '<a target="_blank" href="'. Router::routeUrl('privacy-policy-view').'">'. App::loc('Privacy policy').'</a>'
                ])?></label>
            </p>
            <p>
                <input type="checkbox" id="chkHow">
                <label for="chkHow"><?= App::loc('To what mentioned in {how}', '', [
                    'how' => '<a target="_blank" href="'. Router::routeUrl('how-it-works-view').'">'. App::loc('How it works').'</a>',
                ])?></label>
            </p>
        </div>
        <div class="form-operations">
            <button type="submit" class="btn btn-submit" id="btnSubmit" disabled><?=App::loc('Sign up')?></button>
            <button type="button" class="btn" id="btnCancel"><?=App::loc('Cancel')?></button>
        </div>
    </form>
</section>

<script type="module">
    import {$, $$, errorInResponse, showMessage, generateListOptions} from '/App/js/main.js';
    import xhr from '/App/js/xhr.js';
    import Validator from '/App/js/Validator.js';

    const btnSubmit = $('#btnSubmit');
    const btnCancel = $('#btnCancel');
    const validator = new Validator();

    const lang = '<?= Router::getCurrentLocaleCode()?>';

    $('#frmSignup').addEventListener('submit', operationHandler);
    btnCancel.addEventListener('click', operationHandler);

    $('#chkTOSPP').addEventListener('change', handleAgreement);
    $('#chkHow').addEventListener('change', handleAgreement);

    function handleAgreement(e){
        btnSubmit.disabled = !($('#chkTOSPP').checked && $('#chkHow').checked);
    }
    // Setup validator
    validator.add($('#name'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.name'));
    validator.add($('#surname'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.surname'));
    validator.add($('#country'), '<?= App::loc('Invalid {field}', '', ['field' => ''])?>', $('.validity.country'));
    validator.add($('#mobile'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.mobile'));
    validator.add($('#email'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.email'));
    validator.add($('#password'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''], 1)?>', $('.validity.password'));
    
    validator.add($('#plate_number'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.plate_number'), elem => {
        if(elem.dataset.multiPattern){
            const pn1 = elem.value;
            const pn2 = $('#plate_number2').value;
            const rules = JSON.parse(elem.dataset.multiPattern);

            let isValid = false;
            for(const [r1, r2] of rules){
                if(new RegExp(r1).test(pn1) && new RegExp(r2).test(pn2)){
                    isValid = true;
                }
            }

            return isValid;
        }

        return true;
    });
    validator.add($('#plate_number2'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.plate_number'));
    validator.add($('#maker'), '<?= App::loc('Invalid {field}', '', ['field' => ''])?>', $('.validity.maker'));
    validator.add($('#model'), '<?= App::loc('{field} is required', '', ['field' => ''])?>', $('.validity.model'));
    validator.add($('#color'), '<?= App::loc('{field} is required', '', ['field' => ''])?>', $('.validity.color'));

    // Load country list
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
        }
    });

    // Load makers list
    xhr({
        method: 'GET',
        url: `${lang}/api/Maker/List`,
        callback: resp => {
            if (errorInResponse(resp)) {
                return false;
            }

            generateListOptions($('#maker'), resp.data, 'maker_id', lang != '<?= ALT_LANGUAGE?>'? 'maker': 'maker_alt');
        }
    });

    // Toggle hidden profile option on/off
    $('#gender').addEventListener('change', function(event){
        if(this.value == 'M'){
            $('#hiddenPersonalityOption').classList.add('hidden');
        }else{
            $('#hiddenPersonalityOption').classList.remove('hidden');
        }
        
        if($('#hiddenPersonalityOption').classList.contains('hidden')){
            $('#hidden_personality').checked = false;
        }
    });

    // Toggle car registration on/off
    $('#chkCarRegistration').addEventListener('change', function(event){
        $('#carRegistration').classList.toggle('hidden');
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

    // Update car photo
    $('#imgCarPhoto').addEventListener('load', function(event){
        URL.revokeObjectURL(this.src);
    });

    $('#car_photo').addEventListener('change', function(event){
        if(this.files.length){
            $('#imgCarPhoto').src = URL.createObjectURL(this.files[0]);
        }else{
            $('#imgCarPhoto').src = '/App/img/car.png';
        }
    });

    $('#country').addEventListener('change', function(event){
        // When there is 1 validation rule then we're using one part plate number,so we clear the second one
        if(this.selectedOptions[0].dataset.plate_number_validator.indexOf('&&') === -1){
            $('#plate_number2').value = '';
        }

        setCountryRules({
            backgroundCode: this.value,
            dialingCode: this.selectedOptions[0].dataset.dialing_code,
            mobileValidator: this.selectedOptions[0].dataset.mobile_number_validator,
            plateNumberValidator: this.selectedOptions[0].dataset.plate_number_validator
        });
    });

    // Update car plate background and mobile and plate number validation rules
    function setCountryRules(data){
        $('#plateNumberBackground').className = `plate-number-bakcground ${data.backgroundCode.toLowerCase()}`;
        $('#dialingCode').textContent = data.dialingCode;
        $('#mobile').pattern = data.mobileValidator;

        $('#plate_number2').removeAttribute('pattern');
        $('#plate_number2').required = false;

        // ~~ Multi-rules separator, && plate number parts rule separator
        let pnArr = data.plateNumberValidator.split('~~');

        // We'll use custom validation function for muti-pattern plate numbers with 2 parts (i.e. Egypt)
        delete $('#plate_number').dataset.multiPattern;

        if(pnArr.length > 1){
            $('#plate_number').removeAttribute('pattern');
            $('#plate_number').required = false;
            $('#plate_number2').removeAttribute('pattern');
            $('#plate_number2').required = false;

            pnArr = pnArr.map(item => item.split('&&'));

            $('#plate_number').dataset.multiPattern = JSON.stringify(pnArr);
            return;
        }

        const pn = data.plateNumberValidator.split('&&');

        $('#plate_number').pattern = pn[0];
        
        // This is a special case for plate number consists of 2 parts with 1 fixed pattern (i.e. Saudi Arabia)
        if(pn.length > 1){
            $('#plate_number2').pattern = pn[1];
            $('#plate_number2').required = true;
        }
    }

    // Handle CRUD operations
    function operationHandler(e) {
        e.preventDefault();

        const btnId = e.currentTarget.id;
        
        btnSubmit.disabled = false;

        switch (btnId) {
            case 'btnCancel':
                document.location.href = '<?= Router::routeUrl('home-view')?>';
                break;

            case 'frmSignup':
                validator.clear();

                const carRules = [$('#plate_number'), $('#plate_number2'), $('#maker'), $('#model'), $('#color')];

                if(!validator.validate($('#chkCarRegistration').checked?null: carRules)){
                    showMessage('<?= App::loc('Some data are missing or invalid')?>', 'warning');
                    return;
                }

                // Using formData object in order to upload files
                const data = new FormData();
                
                data.append('gender', $('#gender').value);
                data.append('hidden_personality', $('#hidden_personality').checked?1:0);
                data.append('name', $('#name').value);
                data.append('surname', $('#surname').value);
                data.append('country_code', $('#country').value);
                data.append('mobile', $('#mobile').value);
                data.append('email', $('#email').value);
                data.append('password', $('#password').value);
                data.append('personal_photo', $('#personal_photo').files[0]||'');
                
                if($('#chkCarRegistration').checked){
                    data.append('with_car', 1);
                }

                btnSubmit.disabled = true;
                // Send xhr request
                xhr({
                    method: 'POST',
                    url: `${lang}/api/Account/Signup`,
                    body: data,
                    callback: resp => {
                        btnSubmit.disabled = false;
                        
                        if (errorInResponse(resp)) {
                            return false;
                        }

                        // Registering the car if any
                        registerCar(() => {
                            if(resp.redirect){
                                setTimeout(function(){
                                    document.location.href = resp.redirect;
                                }, 2000);
                            }
                        });
                    }
                });

                break;
        }
    }

    function registerCar(callback){
        if(!$('#chkCarRegistration').checked){
            return callback();
        }

        // Using formData object in order to upload files
        const data = new FormData();
        
        // Add space between 2 parts plate number (Saudi Arabia)
        let pn2 = $('#plate_number2').value.trim();
        if(pn2){
            pn2 = ` ${pn2}`.toUpperCase();
        }

        data.append('plate_number', $('#plate_number').value.toUpperCase() + pn2);
        data.append('maker_id', $('#maker').value);
        data.append('model', $('#model').value);
        data.append('color', $('#color').value);
        data.append('seats_number', $('#seats_number').value);
        data.append('car_photo', $('#car_photo').files[0]||'');
        
        btnSubmit.disabled = true;
        // Send xhr request
        xhr({
            method: 'POST',
            url: `${lang}/api/Car/CreateMyCar`,
            body: data,
            callback: resp => {
                btnSubmit.disabled = false;
                
                if (errorInResponse(resp)) {
                    return false;
                }

                callback();
            }
        });
    }
</script>