<?php

use App\Core\App;
use App\Core\Router;

?>
<section class="data-editor">
    <form id="frmEditor" class="form" novalidate>
        <h2><span id="entityOperation"></span> <?= App::loc('Account')?> <span id="entityId"></span></h2>

        <input type="hidden" id="account_id" data-default="0">

        <div class="control-set">
            <div class="control-group" searchable>
                <p>
                    <label for="account_type"><?= App::loc('Account type')?></label>
                    <span class="validity account_type"></span>
                </p>
                <select id="account_type" data-default="User" data-search-default="">
                    <option value="" search-only></option>
                    <option value="User"><?= App::loc('User')?></option>
                    <option value="Admin"><?= App::loc('Admin')?></option>
                </select>
                <p class="hint">&nbsp;</p>
            </div>
        </div>

        <div class="control-set">
            <div class="control-group" searchable>
                <p>
                    <label for="gender"><?= App::loc('Gender')?></label>
                    <span class="validity gender"></span>
                </p>
                <select id="gender" required autofocus  data-default="M" data-search-default="">
                    <option value="" search-only></option>
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
                <p class="hint">&nbsp;</p>
            </div>
        </div>

        <div class="control-set">
            <div class="control-group" searchable>
                <p>
                    <label for="name"><?= App::loc('Name')?></label>
                    <span class="validity name"></span>
                </p>
                <input type="text" id="name" pattern="[a-zA-Z ء-ي]{3,25}" required>
                <p class="hint">&nbsp;</p>
            </div>

            <div class="control-group" searchable>
                <p>
                    <label for="surname"><?= App::loc('Surname')?></label>
                    <span class="validity surname"></span>
                </p>
                <input type="text" id="surname" pattern="[a-zA-Z ء-ي]{3,25}" required>
                <p class="hint">&nbsp;</p>
            </div>
        </div>
    
        <div class="control-set">
            <div class="control-group" searchable>
                <p>
                    <label for="country"><?= App::loc('Country')?></label>
                    <span class="validity country"></span>
                </p>
                <select id="country" pattern="[A-Z]{2}" required data-search-default=""></select>
                <p class="hint">&nbsp;</p>
            </div>

            <div class="control-group" searchable>
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
            <div class="control-group" searchable>
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
                <input type="password" id="password" autocomplete="new-password">
                <p class="hint"><?= App::loc('6 Characters minimum')?></p>
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
            <div class="control-group" searchable>
                <p>
                    <label for="account_status"><?= App::loc('Account status')?></label>
                    <span class="validity account_status"></span>
                </p>
                <select id="account_status" required data-default="Pending" data-search-default="">
                    <option value="" search-only></option>
                    <option value="Pending"><?= App::loc('Pending')?></option>
                    <option value="Warned"><?= App::loc('Warned')?></option>
                    <option value="Verifying"><?= App::loc('Verifying')?></option>
                    <option value="Active"><?= App::loc('Active')?></option>
                    <option value="Suspended"><?= App::loc('Suspended')?></option>
                    <option value="Deleted"><?= App::loc('Deleted')?></option>
                </select>
                <p class="hint">&nbsp;</p>

                <p>
                    <label for="personal_photo_verification"><?= App::loc('Personal photo verification')?></label>
                    <span class="validity personal_photo_verification"></span>
                </p>
                <select id="personal_photo_verification" data-default="Verified" data-search-default="x">
                    <option value="x"></option>
                    <option value=""><?= App::loc('Not verified')?></option>
                    <option value="Verified"><?= App::loc('Verified')?></option>
                    <option value="Rejected"><?= App::loc('Rejected')?></option>
                </select>
                <p class="hint">&nbsp;</p>

                <p>
                    <label for="remarks"><?= App::loc('Remarks')?></label>
                    <span class="validity remarks"></span>
                </p>
                <input type="text" id="remarks">
                <p class="hint"><?= App::loc('Appears to user')?></p>
            </div>

            <div class="control-group personal-photo">
                <label for="personal_photo"><?= App::loc('Personal photo')?></label>
                <input type="file" id="personal_photo">
                <img src="/App/img/user.png" id="imgPersonalPhoto">
                <p class="hint"><?= App::loc('Clear photo of your face')?></p>
            </div>
        </div>

        <div class="control-set">
            <div class="control-group" searchable>
                <p>
                    <label for="admin_notes"><?= App::loc('Admin notes')?></label>
                    <span class="validity admin_notes"></span>
                </p>
                <textarea id="admin_notes" rows="3"></textarea>
            </div>

            <div class="control-group" id="verificationStatus">
                <input type="hidden" id="email_verification">
                <input type="hidden" id="mobile_verification">

                <label><?= App::loc('Verification status')?></label>
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
                            <?php else:?>
                                <p class="hint hidden" id="smsVerificationNumber"></p>
                            <?php endif?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="control-set">
            <div class="control-group" searchable search-only>
                <p>
                    <label for="register_date_from"><?= App::loc('Register date from')?></label>
                    <span class="register_date_from"></span>
                </p>
                <input type="date" id="register_date_from">
                <p class="hint">&nbsp;</p>
            </div>

            <div class="control-group" searchable search-only>
                <p>
                    <label for="register_date_to"><?= App::loc('Register date to')?></label>
                    <span class="register_date_to"></span>
                </p>
                <input type="date" id="register_date_to">
                <p class="hint">&nbsp;</p>
            </div>
        </div>

        <div class="form-operations">
            <button type="submit" class="btn btn-submit" id="btnSubmit"><?=App::loc('Save')?></button>
            <button type="button" class="btn" id="btnCancel"><?=App::loc('Cancel')?></button>
        </div>
    </form>
</section>

<section class="info-card">
    <div class="form">
        <button id="btnCloseInfo" class="close btn btn-black">✖</button>
        <h2>
            <span class="info-data" id="info_header">&nbsp;</span>
        </h2>

        <table>
            <tr>
                <th><?= App::loc('ID')?></th>
                <td>
                    <span class="info-data" id="info_account_id"></span>
                    <div class="account-image-container">
                        <img id="imgInfoPersonalPhoto" src="">
                        <span class="tag" id="infoPhotoVerification">&nbsp;</span>
                    </div>
                </td>
            </tr>
            <tr>
                <th><?= App::loc('Account type')?></th>
                <td class="info-data" id="info_account_type"></td>
            </tr>
            <tr>
                <th><?= App::loc('Gender')?></th>
                <td class="info-data" id="info_gender"></td>
            </tr>
            <tr>
                <th><?= App::loc('Hidden personality')?></th>
                <td class="info-data" id="info_hidden_personality"></td>
            </tr>
            <tr>
                <th><?= App::loc('Name')?></th>
                <td class="info-data" id="info_name"></td>
            </tr>
            <tr>
                <th><?= App::loc('Surname')?></th>
                <td class="info-data" id="info_surname"></td>
            </tr>
            <tr>
                <th><?= App::loc('Country')?></th>
                <td class="info-data" id="info_country"></td>
            </tr>
            <tr>
                <th><?= App::loc('Email')?></th>
                <td class="flex">
                    <span class="info-data" id="info_email"></span>
                    <span class="tag" id="infoEmailVerification">&nbsp;</span>
                </td>
            </tr>
            <tr>
                <th><?= App::loc('Mobile')?></th>
                <td class="flex">
                    <span class="info-data" id="info_mobile"></span>
                    <span class="tag" id="infoMobileVerification">&nbsp;</span>
                </td>
            </tr>
            <tr>
                <th><?= App::loc('Preferred language')?></th>
                <td class="info-data" id="info_preferred_language"></td>
            </tr>
            <tr>
                <th><?= App::loc('Notification emails')?></th>
                <td class="info-data" id="info_notification_emails"></td>
            </tr>
            <tr>
                <th><?= App::loc('Account status')?></th>
                <td class="info-data" id="info_account_status"></td>
            </tr>
            <tr>
                <th><?= App::loc('Ratings count')?></th>
                <td class="info-data" id="info_ratings_count"></td>
            </tr>
            <tr>
                <th><?= App::loc('Rating')?></th>
                <td class="info-data" id="info_rating"></td>
            </tr>
            <tr>
                <th><?= App::loc('Admin notes')?></th>
                <td class="info-data" id="info_admin_notes"></td>
            </tr>
            <tr>
                <th><?= App::loc('Register date')?></th>
                <td class="info-data date-time" id="info_register_date"></td>
            </tr>
            <tr>
                <th><?= App::loc('Remarks')?></th>
                <td class="info-data" id="info_remarks"></td>
            </tr>
        </table>
    </div>
</section>

<section class="data-list">
    <h2 class="accounts-manager"><i class="icon-user"></i> <?= App::loc('Accounts manager')?></h2>

    <div class="toolbar">
        <div class="toolbar-group">
            <button class="btn btn-green" id="btnCreate"><?= App::loc('Create')?></button>
            <button class="btn btn-orange" id="btnUpdate"><?= App::loc('Update')?></button>
            <button class="btn btn-red" id="btnDelete"><?= App::loc('Delete')?></button>
            <button class="btn btn-blue" id="btnRefresh"><?= App::loc('Refresh')?></button>
            <button class="btn btn-gray" id="btnInfo"><?= App::loc('Info')?></button>
        </div>
        <div class="toolbar-group pagening">
            <button class="btn btn-yellow" id="btnSearch"><i class="icon-search"></i></button>
            <input class="page-number" type="number" id="txtPageNumber" min="1" value="1">
            <button class="btn btn-yellow" id="btnPrevious"><i class="icon-chevron-left"></i></button>
            <button class="btn btn-yellow" id="btnNext"><i class="icon-chevron-right"></i></button>
        </div>
    </div>

    <div class="data-grid-wrapper">
        <table class="data-grid" id="tblData">
            <thead>
            <tr>
                <th data-model="account_id" use-value="account_id"><?= App::loc('ID')?></th>
                <th data-model="account_type" data-class="auto-width"><?= App::loc('Account type')?></th>
                <th data-model="gender" data-class="auto-width"><?= App::loc('Gender')?></th>
                <th data-model="name" data-class="auto-width"><?= App::loc('Name')?></th>
                <th data-model="surname" data-class="auto-width"><?= App::loc('Surname')?></th>
                <th data-model="country" data-class="auto-width uppercase"><?= App::loc('Country')?></th>
                <th data-model="register_date" data-class="auto-width ltr"><?= App::loc('Register date')?></th>
                <th data-model="account_status" data-class="auto-width"><?= App::loc('Account status')?></th>
                <th data-model="ratings_count" data-class="auto-width"><?= App::loc('Ratings count')?></th>
                <th data-model="rating" data-class="auto-width"><?= App::loc('Rating')?></th>
            </tr>
            </thead>
            <tfoot>
            <tr>
                <td colspan="10" id="tdPageRecords"></td>
            </tr>
            </tfoot>
        </table>
    </div>
</section>

<script type="module">
    import {$, $$, generateListOptions, updateRecordsStats, resetForm, resetCard, errorInResponse, showMessage, showDataEditor, hideDataEditor, showDialog, hideDialog} from '/App/js/main.js';
    import Validator from '/App/js/Validator.js';
    import TableManager from '/App/js/TableManager.js';
    import xhr from '/App/js/xhr.js';
    import Prompt, {Action} from '/App/js/Prompt.js';

    const secDataEditor = $('.data-editor');
    const secInfoCard = $('.info-card');
    const btnSubmit = $('#btnSubmit');
    const btnCancel = $('#btnCancel');
    const txtPageNumber = $('#txtPageNumber');
    let currentOper = '';
    let currentPage = 1;
    let totalPages = 1;
    let totalRecords = 0;
    let currentSearch = null;

    const lang = '<?= Router::getCurrentLocaleCode()?>';

    const validator = new Validator();
    const tblMgr = new TableManager($('#tblData'));

    let carPhotosCount = 0;
    let carsCount = 0;

    const promptAccountDelete = new Prompt(
        '',
        [
            new Action('btnCancel', '<?= App::loc('Cancel')?>', false, 'btn'),
            new Action('btnDelete', '<?= App::loc('Delete')?>', true, 'btn btn-red')
        ]
    );

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

    const accountType = {
        Admin: '<?= App::loc('Admin')?>',
        User: '<?= App::loc('User')?>'
    };

    const gender = {
        M: '<?= App::loc('M')?>',
        F: '<?= App::loc('F')?>'
    };
    
    const accountStatus = {
        Pending: '<?= App::loc('Pending')?>',
        Warned: '<?= App::loc('Warned')?>',
        Verifying: '<?= App::loc('Verifying')?>',
        Active: '<?= App::loc('Active')?>',
        Suspended: '<?= App::loc('Suspended')?>',
        Deleted: '<?= App::loc('Deleted')?>'
    };
    
    const preferredLanguage = {
        ar: '<?= App::loc('ar')?>',
        en: '<?= App::loc('en')?>'
    };

    const ratingDescriptions = [
        '<?= App::loc('Unspecified')?>',
        '<?= App::loc('Bad')?>',
        '<?= App::loc('Okay')?>',
        '<?= App::loc('Good')?>',
        '<?= App::loc('Very good')?>',
        '<?= App::loc('Excellent')?>'
    ];

    $('#frmEditor').addEventListener('submit', operationHandler);
    btnCancel.addEventListener('click', operationHandler);
    $('#btnCreate').addEventListener('click', operationHandler);
    $('#btnUpdate').addEventListener('click', operationHandler);
    $('#btnDelete').addEventListener('click', operationHandler);
    $('#btnRefresh').addEventListener('click', operationHandler); 
    $('#btnPrevious').addEventListener('click', operationHandler);
    $('#btnNext').addEventListener('click', operationHandler);
    $('#txtPageNumber').addEventListener('change', operationHandler);
    $('#btnSearch').addEventListener('click', operationHandler);
    $('#btnInfo').addEventListener('click', operationHandler);
    $('#btnCloseInfo').addEventListener('click', operationHandler);
    $('#btnEmailVerification').addEventListener('click', operationHandler);
    <?php if(MOBILE_VERIFICATION_MODE == 'Send'):?>
        $('#btnMobileVerification').addEventListener('click', operationHandler);
    <?php endif?>

    tblMgr.events.listen('cell-render', (col, value, data) => {
        switch(col){
            case 'account_type':
                return accountType[value]||value;

            case 'gender':
                return gender[value]||value;

            case 'account_status':
                return accountStatus[value]||value;
            
            case 'country':
                return lang != '<?= ALT_LANGUAGE?>'? data['country']: data['country_alt'];
            
            case 'rating':
                return `<span class="bidi">${value}<i class="icon-star"></i> ${ratingDescriptions[parseInt(value)]||value}</span>`;
        }
    });

    // Setup validator
    validator.add($('#name'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.name'));
    validator.add($('#surname'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.surname'));
    validator.add($('#country'), '<?= App::loc('Invalid {field}', '', ['field' => ''])?>', $('.validity.country'));
    validator.add($('#mobile'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.mobile'));
    validator.add($('#email'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''])?>', $('.validity.email'));
    validator.add($('#password'), '<?= App::loc('Invalid or missing {field}', '', ['field' => ''], 1)?>', $('.validity.password'));
    
    // Account delete confirmation
    promptAccountDelete.events.listen('Action', action => {
        if(action.name != 'btnDelete'){
            return;
        }

        sendRequest(
            'POST',
            'Delete',
            [],
            {
                account_id: action.data.accountID
            }
        );
    });

    // Sending email verification link confirmation
    promptEmailVerification.events.listen('Action', action => {
        if(action.name != 'btnSend'){
            return;
        }

        sendRequest(
            'POST',
            'SendVerificationEmail',
            [],
            {
                account_id: action.data.accountID
            }
        );
    });

    // Sending mobile verification SMS confirmation
    promptMobileVerification.events.listen('Action', action => {
        if(action.name != 'btnSend'){
            return;
        }

        sendRequest(
            'POST',
            'SendVerificationSMS',
            [],
            {
                account_id: action.data.accountID
            }
        );
    });

    // Load country list
    xhr({
        method: 'GET',
        url: `${lang}/api/Country/List`,
        callback: resp => {
            if (errorInResponse(resp)) {
                return false;
            }

            const selCountry = $('#country');
            generateListOptions(selCountry, resp.data, 'country_code', lang != '<?= ALT_LANGUAGE?>'? 'country': 'country_alt', '' ,['dialing_code', 'mobile_number_validator', 'plate_number_validator']);
            const emptyOption = new Option('', '');
            emptyOption.setAttribute('search-only', '');
            if(resp.data.length){
                selCountry.insertBefore(emptyOption, selCountry.options[0]);
            }else{
                selCountry.appendChild(emptyOption);
            }

            // Trggier the change event in order to update necessary items
            selCountry.dispatchEvent(new Event('change'));
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

    function setCountryRules(data){
        $('#dialingCode').textContent = data.dialingCode;
        $('#mobile').pattern = data.mobileValidator;
    }

    $('#country').addEventListener('change', function(event){
        setCountryRules({
            dialingCode: this.selectedOptions[0].dataset.dialing_code,
            mobileValidator: this.selectedOptions[0].dataset.mobile_number_validator
        });
    });

    // Handle CRUD operations
    function operationHandler(e) {
        e.preventDefault();

        const btnId = e.currentTarget.id;
        let uriParams = [];
        
        btnSubmit.disabled = false;

        let accountID = null;

        if (tblMgr.selectedRow) {
            accountID = tblMgr.getCellValue(tblMgr.selectedRow.rowIndex - 1, 'account_id');
        }

        switch (btnId) {
            case 'btnCancel':
                hideDataEditor(secDataEditor);
                break;

            case 'btnSearch':
                btnSubmit.textContent = '<?= App::loc('Search')?>';
                currentOper = 'Search';

                $('#entityOperation').textContent = btnSubmit.textContent;
                $('#entityId').textContent = '';
                resetForm($('#frmEditor'), true);
                validator.clear();
                $('#verificationStatus').classList.add('hidden');
                showDataEditor(secDataEditor, 'search');
                break;

            case 'btnCreate':
                btnSubmit.textContent = '<?= App::loc('Create')?>';
                currentOper = 'Create';

                $('#entityOperation').textContent = btnSubmit.textContent;
                $('#entityId').textContent = '';
                resetForm($('#frmEditor'));
                $('#gender').dispatchEvent(new Event('change'));
                $('#country').dispatchEvent(new Event('change'));
                $('#imgPersonalPhoto').src = '/App/img/user.png';
                validator.clear();
                $('#verificationStatus').classList.add('hidden');
                showDataEditor(secDataEditor, 'create');
                break;

            case 'btnUpdate':
                if (!accountID) {
                    showMessage('<?= App::loc('Please select a record first')?>', 'warning');
                    return false;
                }

                btnSubmit.textContent = '<?= App::loc('Update')?>';
                currentOper = 'Update';

                $('#entityOperation').textContent = btnSubmit.textContent;
                $('#entityId').textContent = accountID;

                fillForm(accountID);
                $('#verificationStatus').classList.remove('hidden');
                showDataEditor(secDataEditor, 'update');
                break;

            case 'btnInfo':
                if (!accountID) {
                    showMessage('<?= App::loc('Please select a record first')?>', 'warning');
                    return false;
                }

                currentOper = 'Read';

                fillCard(accountID);
                showDialog(secInfoCard);
                break;

            case 'btnCloseInfo':
                hideDialog(secInfoCard);
                break;

            case 'btnDelete':
                if (!accountID) {
                    showMessage('<?= App::loc('Please select a record first')?>', 'warning');
                    return false;
                }

                const name = tblMgr.getCellText(tblMgr.selectedRow.rowIndex - 1, 'name');
                const surname = tblMgr.getCellText(tblMgr.selectedRow.rowIndex - 1, 'surname');

                promptAccountDelete.setDescription(`<?= App::loc('Delete account #${accountID} for ${name} ${surname}')?>`)
                promptAccountDelete.setActionData('btnDelete', {
                    accountID
                });
                
                promptAccountDelete.show();
                currentOper = 'Delete';
                
                break;
                
            case 'btnEmailVerification':
                if (!accountID) {
                    showMessage('<?= App::loc('Please select a record first')?>', 'warning');
                    return false;
                }

                const email = e.target.dataset.email;

                promptEmailVerification.setDescription(`<?= App::loc('Send email verification link to ${email}')?>`);
                promptEmailVerification.setActionData('btnSend', {
                    accountID
                });
                
                promptEmailVerification.show();
                currentOper = 'EmailVerification';
                
                break;
            
            <?php if(MOBILE_VERIFICATION_MODE == 'Send'):?>
                case 'btnMobileVerification':
                    if (!accountID) {
                        showMessage('<?= App::loc('Please select a record first')?>', 'warning');
                        return false;
                    }

                    const mobile = e.target.dataset.mobile;

                    promptMobileVerification.setDescription(`<?= App::loc('Send mobile verification SMS to ${mobile}')?>`)
                    promptMobileVerification.setActionData('btnSend', {
                        accountID
                    });

                    promptMobileVerification.show();
                    currentOper = 'MobileVerification';

                    break;
            <?php endif?>

            case 'btnRefresh':
                // Reset search params on Refresh operation
                currentSearch = null;

            // All these commands are Read operation
            case 'btnPrevious':
            case 'btnNext':
            case 'txtPageNumber':
                btnSubmit.textContent = '';
                currentOper = 'Read';

                // Pagination
                let page = null;

                if(btnId == 'btnPrevious'){
                    currentPage -= 1;
                    page = currentPage;
                }
                
                if(btnId == 'btnNext'){
                    currentPage += 1;
                    page = currentPage;
                }

                if(btnId == 'txtPageNumber'){
                    currentPage = txtPageNumber.value;
                    page = currentPage;
                }

                if(btnId == 'btnRefresh'){
                    currentPage = 1;
                    totalPages = 1;
                    txtPageNumber.value = currentPage;
                }
                
                if(currentPage < 1){
                    currentPage = 1;
                    txtPageNumber.value = currentPage;

                    showMessage('No more records', 'info');
                    return;
                }

                if(currentPage > totalPages){
                    currentPage = totalPages;
                    txtPageNumber.value = currentPage;

                    showMessage('No more records', 'info');
                    return;
                }

                if(page){
                    uriParams.push('P', page);
                    txtPageNumber.value = page;
                }

            case 'frmEditor':                
                let data = {};

                if(currentOper == 'Search'){
                    currentSearch = JSON.stringify({
                        account_type: $('#account_type').value,
                        gender: $('#gender').value,
                        name: $('#name').value,
                        surname: $('#surname').value,
                        country_code: $('#country').value,
                        mobile: $('#mobile').value,
                        email: $('#email').value,
                        personal_photo_verification: $('#personal_photo_verification').value,
                        account_status: $('#account_status').value,
                        remarks: $('#remarks').value,
                        admin_notes: $('#admin_notes').value,
                        register_date_from: $('#register_date_from').value,
                        register_date_to: $('#register_date_to').value
                    });

                    currentOper = 'Read';
                }

                if (['Create', 'Update'].indexOf(currentOper) > -1) {
                    if(!validator.validate(currentOper == 'Edit'?[$('#password')]:null)){
                        showMessage('<?= App::loc('Some data are missing or invalid')?>', 'warning');
                        return;
                    }

                     // Using formData object in order to upload files
                    data = new FormData();

                    data.append('account_id', $('#account_id').value);
                    data.append('account_type', $('#account_type').value);
                    data.append('gender', $('#gender').value);
                    data.append('hidden_personality', $('#hidden_personality').checked?1:0);
                    data.append('name', $('#name').value);
                    data.append('surname', $('#surname').value);
                    data.append('country_code', $('#country').value);
                    data.append('email', $('#email').value);
                    data.append('mobile', $('#mobile').value);
                    data.append('password', $('#password').value);
                    data.append('preferred_language', $('#preferred_language').value);
                    data.append('notification_emails', $('#notification_emails').checked?1:0);
                    data.append('remarks', $('#remarks').value);
                    data.append('admin_notes', $('#admin_notes').value);
                    data.append('account_status', $('#account_status').value);
                    data.append('personal_photo', $('#personal_photo').files[0]||'');
                    data.append('personal_photo_verification', $('#personal_photo_verification').value);
                    data.append('email_verification', $('#email_verification').value);
                    data.append('mobile_verification', $('#mobile_verification').value);
                }

                // Including search params for Search, Next, Previous and Page Number operations
                if(currentSearch){
                    if(data instanceof FormData){
                        data.append('search', currentSearch);
                    }else{
                        data.search = currentSearch;
                    }
                }
                
                btnSubmit.disabled = true;
                // Determine request method
                const method = ['Create', 'Update'].includes(currentOper)?'POST': 'GET';
                
                sendRequest(method, currentOper, uriParams, data);

                break;
        }
    }

    // Send xhr request
    function sendRequest(method = 'GET', dbOper = '', uriParams = [], body = {}){
        let rowIndex = -1;

        if (tblMgr.selectedRow) {
            // The row index in the table body
            rowIndex = tblMgr.selectedRow.rowIndex - 1;
        }

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

                // Hide the form
                btnCancel.click();

                // Handle received data
                switch (dbOper) {
                    case 'Read':
                        tblMgr.selectedRow = null;
                        
                        // Clear the table before appending rows
                        tblMgr.renderTable(resp.data, true);

                        // Page / Records
                        totalRecords = resp.metaData?resp.metaData.total_records:resp.data.length;
                        totalPages = Math.ceil(totalRecords / (resp.metaData?resp.metaData.records_per_page:totalRecords));
                        
                        updateRecordsStats($('#tdPageRecords'), totalRecords, currentPage, totalPages, '<?= Router::getCurrentLocaleCode()?>');
                        break;

                    case 'Create':
                        tblMgr.addRow(resp.data);

                        updateRecordsStats($('#tdPageRecords'), ++totalRecords, currentPage, totalPages, '<?= Router::getCurrentLocaleCode()?>');
                        break;

                    case 'Update':
                        tblMgr.updateRow(resp.data, rowIndex);
                        break;
                    
                    case 'Delete':
                        tblMgr.removeRow(rowIndex);

                        updateRecordsStats($('#tdPageRecords'), --totalRecords, currentPage, totalPages, '<?= Router::getCurrentLocaleCode()?>');
                        break;
                }
            }
        });
    }

    function fillForm(id) {
        resetForm($('#frmEditor'));
        $('#imgPersonalPhoto').src = '/App/img/user.png';

        validator.clear();
        btnSubmit.disabled = true;

        $('#entityId').textContent = '';

        xhr({
            method: 'GET',
            url: `${lang}/api/Account/Read/${id}`,
            callback: resp => {
                btnSubmit.disabled = false;
                
                if (errorInResponse(resp)) {
                    return false;
                }

                if(resp.data.length == 0){
                    showMessage('<?= App::loc('No data were found')?>', 'warning');
                    btnSubmit.disabled = true;

                    return false;
                }

                const data = resp.data[0];

                $('#entityId').textContent = `#${data['account_id']}`;
                $('#account_id').value = data['account_id'];
                $('#account_type').value = data['account_type'];
                $('#gender').value = data['gender'];
                $('#hidden_personality').checked = !!parseInt(data['hidden_personality']);
                $('#name').value = data['name'];
                $('#surname').value = data['surname'];
                $('#country').value = data['country_code'];
                $('#email').value = data['email'];
                $('#mobile').value = data['mobile'];
                $('#password').value = '';
                $('#preferred_language').value = data['preferred_language'];
                $('#notification_emails').checked = !!parseInt(data['notification_emails']);
                $('#remarks').value = data['remarks'];
                $('#admin_notes').value = data['admin_notes'];
                $('#account_status').value = data['account_status'];
                $('#personal_photo_verification').value = data['personal_photo_verification'];
                $('#email_verification').value = data['email_verification'];
                $('#mobile_verification').value = data['mobile_verification'];
                $('#imgPersonalPhoto').src = '<?= Router::routeUrl('account-photo')?>'.replace('{photo_path}', data['personal_photo']);
                
                $('#btnEmailVerification').dataset.email = data['email'];
                <?php if(MOBILE_VERIFICATION_MODE == 'Send'):?>
                    $('#btnMobileVerification').dataset.mobile = `${data['dialing_code']} ${data['mobile']}`;
                <?php endif?>
                
                $('#gender').dispatchEvent(new Event('change'));
                $('#country').dispatchEvent(new Event('change'));

                const photoVer = $('#photoVerification');
                const emailVer = $('#emailVerification');
                const mobileVer = $('#mobileVerification');
                const btnEmailVer = $('#btnEmailVerification');
                <?php if(MOBILE_VERIFICATION_MODE == 'Send'):?>
                    const btnMobileVer = $('#btnMobileVerification');
                <?php else:?>
                    const smsVerNumber = $('#smsVerificationNumber');
                <?php endif?>

                photoVer.classList.remove('tag-verified', 'tag-not-verified', 'tag-rejected');
                emailVer.classList.remove('tag-verified', 'tag-not-verified');
                mobileVer.classList.remove('tag-verified', 'tag-not-verified');

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

                if(data['mobile_verification'] == 'Verified'){
                    mobileVer.textContent = '<?= App::loc('Verified')?>';
                    mobileVer.classList.add('tag-verified');
                    <?php if(MOBILE_VERIFICATION_MODE == 'Send'):?>
                        btnMobileVer.classList.add('hidden');
                    <?php else:?>
                        smsVerNumber.innerHTML = '';
                        smsVerNumber.classList.add('hidden');
                    <?php endif?>
                }else{
                    mobileVer.textContent = '<?= App::loc('Not verified')?>';
                    mobileVer.classList.add('tag-not-verified');
                    <?php if(MOBILE_VERIFICATION_MODE == 'Send'):?>
                        btnMobileVer.classList.remove('hidden');
                    <?php else:?>
                        smsVerNumber.innerHTML = `<?= App::loc('Verify your mobile by sending the code {code} via SMS to this number {sms_number}', '', ['sms_number' => $params['sms_virtual_number']])?>`.replace('{code}', data['mobile_verification']);
                        smsVerNumber.classList.remove('hidden');
                    <?php endif?>
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
            }
        });
    }

    function fillCard(id) {
        resetCard(secInfoCard);

        xhr({
            method: 'GET',
            url: `${lang}/api/Account/Read/${id}`,
            callback: resp => {
                if (errorInResponse(resp)) {
                    return false;
                }

                if(resp.data.length == 0){
                    showMessage('<?= App::loc('No data were found')?>', 'warning');

                    return false;
                }
                
                let data = resp.data[0];
                
                $('#info_header').textContent = `${data['name']} ${data['surname']}`;
                $('#info_account_id').textContent = data['account_id'];
                $('#info_account_type').textContent = accountType[data['account_type']]||data['account_type'];
                $('#info_gender').textContent = gender[data['gender']]||data['gender'];
                $('#info_hidden_personality').textContent = !!parseInt(data['hidden_personality'])?'<?= App::loc('Yes') ?>': '<?= App::loc('No') ?>';
                $('#info_name').textContent = data['name'];
                $('#info_surname').textContent = data['surname'];
                $('#info_country').textContent = data['country'];
                $('#info_email').innerHTML = `<a href="mailto:${data['email']}" dir="ltr">${data['email']}</a>`;
                $('#info_mobile').innerHTML = `<a href="tel:${data['dialing_code']}${data['mobile']}" dir="ltr">${data['dialing_code']} ${data['mobile']}</a> ${data['mobile_verification'] == 'Verified'?'': '<span class="no-wrap"><?= App::loc('Verification code')?> <b dir="ltr">' + data['mobile_verification'] + '</b></span>'}`;
                $('#info_preferred_language').textContent = preferredLanguage[data['preferred_language']]||data['preferred_language'];
                $('#info_notification_emails').textContent = !!parseInt(data['notification_emails'])?'<?= App::loc('Yes') ?>': '<?= App::loc('No') ?>';
                $('#info_remarks').textContent = data['remarks'];
                $('#info_admin_notes').textContent = data['admin_notes'];
                $('#info_register_date').textContent = data['register_date'];
                $('#info_account_status').textContent = accountStatus[data['account_status']]||data['account_status'];
                $('#info_ratings_count').textContent = accountStatus[data['ratings_count']]||data['ratings_count'];
                $('#info_rating').innerHTML = `<span class="bidi">${data['rating']}<i class="icon-star"></i> ${ratingDescriptions[parseInt(data['rating'])]||data['rating']}</span>`;
                $('#imgInfoPersonalPhoto').src = '<?= Router::routeUrl('account-photo')?>'.replace('{photo_path}', data['personal_photo']);
                
                const photoVer = $('#infoPhotoVerification');
                const emailVer = $('#infoEmailVerification');
                const mobileVer = $('#infoMobileVerification');

                photoVer.classList.remove('tag-verified', 'tag-not-verified', 'tag-rejected');
                emailVer.classList.remove('tag-verified', 'tag-not-verified');
                mobileVer.classList.remove('tag-verified', 'tag-not-verified');

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

                if(data['mobile_verification'] == 'Verified'){
                    mobileVer.textContent = '<?= App::loc('Verified')?>';
                    mobileVer.classList.add('tag-verified');
                }else{
                    mobileVer.textContent = '<?= App::loc('Not verified')?>';
                    mobileVer.classList.add('tag-not-verified');
                }

                if(data['email_verification'] == 'Verified'){
                    emailVer.textContent = '<?= App::loc('Verified')?>';
                    emailVer.classList.add('tag-verified');
                }else{
                    emailVer.textContent = '<?= App::loc('Not verified')?>';
                    emailVer.classList.add('tag-not-verified');
                }
                
            }
        });
    }

    const ids = '<?=$params['ids']??''?>';
    let status = '<?=$params['status']??''?>';
    const params = [];
    const body = {};

    if(ids){
        params.push(ids);
    }

    let personalPhotoVerification = 'x';
    if(status){
        if(status == 'VerifyingPhoto'){
            status = 'Verifying';
            personalPhotoVerification = '';
        }

        body.search = JSON.stringify({account_status: status, personal_photo_verification: personalPhotoVerification});
    }

    sendRequest('GET', 'Read', params, body);
</script>
