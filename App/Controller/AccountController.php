<?php
declare (strict_types = 1);
namespace App\Controller;

use App\Core\Controller;
use App\Core\App;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Crypto;
use App\Model\Account;
use App\Core\Validator;
use App\Core\ValidationRule;
use App\Core\Result;
use App\Core\Router;
use App\Core\Request;
use App\Service\AccountService;
use App\Service\AttachmentService;
use App\Service\MailService;
use App\Service\NotificationService;
use App\Service\SMSService;

class AccountController extends Controller
{
    private Account $account;
    private array $data = [];
    private array $search;
    private Validator $validator;

    public function __construct(array $data = [])
    {
        $this->account = new Account();

        // Setup defaults
        $data = App::setupDefaults($data, [
            'account_id' => 0,
            'account_type' => 'User', // User, Admin
            'gender' => 'M', // M, F
            'hidden_personality' => 0, // When gender is F account can set this to hide photo, email and mobile from gender M
            'name' => '',
            'surname' => '',
            'country_code' => '',
            'email' => '',
            'email_verification' => '',
            'mobile' => '',
            'mobile_verification' => '',
            'password' => '',
            'preferred_language' => 'en',
            'notification_emails' => 1,
            'personal_photo' => '',
            'personal_photo_verification' => '',
            'register_date' => date('Y-m-d H:i:s'),
            'admin_notes' => '',
            'account_status' => 'Pending', // Pending, Warned, Verifiying, Active, Suspended, Deleted
            'remarks' => ''
        ]);
        // Get search params from both request body and query string
        $this->search = array_merge(json_decode($data['search'] ?? '[]', true), json_decode(Request::getQueryParams()['search'] ?? '[]', true));
        
        $data['hidden_personality'] = intval($data['hidden_personality']);
        $data['notification_emails'] = intval($data['notification_emails']);

        // Only hash password if not empty
        if(!empty($data['password'])){
            $data['password'] = hash('sha256', $data['password']);
        }

        $this->data = $data;

        // Get validation rules for mobile numbers based on account's related country
        $countryMobileeRule = '.+';
        $rulesResult = $this->account->CountryValidationRules([
            'account_id' => $this->data['account_id']
        ]);

        if(!empty($rulesResult->data)){
            $countryMobileeRule = $rulesResult->data[0]['mobile_number_validator'];
        }

        $this->validator = new Validator($this->data);
        $this->validator->add('account_id', 'Invalid account id', ValidationRule::number(1));
        $this->validator->add('account_type', 'Invalid account type', ValidationRule::inList(['User', 'Admin']));
        $this->validator->add('gender', 'Invalid gender', ValidationRule::inList(['M', 'F']));
        $this->validator->add('name', 'Name is missing or inavlid', ValidationRule::string(3, 25));
        $this->validator->add('surname', 'Surname is missing or inavlid', ValidationRule::string(3, 25));
        $this->validator->add('country_code', 'Invalid country', ValidationRule::regexp('#[A-Z]{2}#'));
        $this->validator->add('email', 'Invalid email', ValidationRule::email());
        $this->validator->add('mobile', 'Invalid mobile', function($value)use($countryMobileeRule){
            return ValidationRule::regexp('#' . $countryMobileeRule. '#')($value);
        });
        $this->validator->add('password', 'Password is missing', ValidationRule::notEmpty());
        $this->validator->add('preferred_language', 'Invalid language', ValidationRule::inList(['en', 'ar']));
        $this->validator->add('account_status', 'Invalid account status', ValidationRule::inList(['Pending', 'Warned', 'Verifying', 'Active', 'Suspended', 'Deleted']));
    }

    // Guard: Anonymous
    public function SignUp(array $routeParams = []): Result
    {
        $this->data['account_type'] = 'User';
        $this->data['account_status'] = 'Pending';
        $this->data['remarks'] = '';
        // Use current display language as user preferred langauge by default
        $this->data['preferred_language'] = Router::getCurrentLocaleCode();

        $result = $this->Create($routeParams);

        if(in_array($result->messageType, ['success', 'warning'])){
            $account = $result->data[0];
            $accountID = $result->data[0]['account_id'];

            // It also required for the thank you page params
            $tempUserData = [
                'name' => $account['name'],
                'surname' => $account['surname'],
                'mobile' => $account['mobile'],
                'email' => $account['email'],
                'preferred_language' => $account['preferred_language'],
            ];
            
            // Save account_id and account_status which is required for registering first car from sign up page
            // is_signing_up is checked on car controller to indicate creating a car upon signing up process in order to clear temp data
            if($this->data['with_car']){
                $tempUserData['account_id'] = $accountID;
                $tempUserData['account_status'] = 'Verifying';
                $tempUserData['is_signing_up'] = true;
            }

            Auth::setUser($tempUserData);

            // Redirect to Thank you page
            $result->redirect = Router::routeUrl('thank-you-view');
            
            $this->data['account_id'] = $accountID;

            // Send verification SMS if verification mode is Send
            if(MOBILE_VERIFICATION_MODE == 'Send'){
                $resVerifySMS = $this->SendVerificationSMS();
                $result->messageType = $resVerifySMS->messageType;
            }

            $result->message .= "\n" . App::loc('Thanks for signing up to {website}', $account['preferred_language'], ['website' => App::loc(WEBSITE_TITLE, $account['preferred_language'])]);
        }

        return $result;
    }
    
    // Guard: Admin
    public function Create(array $routeParams = []): Result
    {
        if($dataErr = $this->validator->validate(['account_id'])){
            return new Result(
                $dataErr,
                App::loc('Some data are missing or invalid'),
                'validation_error'
            );
        }

        $resDuplicate = $this->account->isDuplicated([
            'email' => $this->data['email'],
            'mobile' => $this->data['mobile']
        ]);

        // DB Error
        if(is_null($resDuplicate->data)){
            return $resDuplicate;
        }

        // Existing record found
        if($resDuplicate->data > 0){
            return new Result(
                null,
                App::loc('{object} exists with ID #{id}', '', ['object' => 'Account', 'id' => $resDuplicate->data]),
                'error'
            );
        }

        $emailVerificationCode = AccountService::generateEmailVerification($this->data['email']);
        $mobileVerificationCode = '';

        if(MOBILE_VERIFICATION_MODE == 'Receive'){
            $mobileVerificationCode = AccountService::generateMobileVerification();
        }

        // If account status set as Active by admin then update all verification status
        $this->data['email_verification'] = $emailVerificationCode;
        $this->data['mobile_verification'] = $mobileVerificationCode;
        $this->data['personal_photo_verification'] = '';

        if($this->data['account_status'] == 'Active'){
            $this->data['email_verification'] = 'Verified';
            $this->data['mobile_verification'] = 'Verified';
            $this->data['personal_photo_verification'] = 'Verified';
        }

        $resCreate = $this->account->Create([
            'account_type' => $this->data['account_type'],
            'gender' => $this->data['gender'],
            'hidden_personality' => $this->data['hidden_personality'],
            'name' => $this->data['name'],
            'surname' => $this->data['surname'],
            'country_code' => $this->data['country_code'],
            'email' => $this->data['email'],
            'email_verification' => $this->data['email_verification'],
            'mobile' => $this->data['mobile'],
            'mobile_verification' => $this->data['mobile_verification'],
            'password' => $this->data['password'],
            'preferred_language' => $this->data['preferred_language'],
            'notification_emails' => $this->data['notification_emails'],
            'personal_photo_verification' => $this->data['personal_photo_verification'], // manually set by admin
            'register_date' => date('Y-m-d H:i:s'),
            'admin_notes' => $this->data['admin_notes'],
            'admin_notes' => $this->data['admin_notes'],
            'account_status' => $this->data['account_status'],
            'remarks' => $this->data['remarks']
        ]);
        
        // DB Error
        if(is_null($resCreate->data)){
            return $resCreate;
        }

        $accountID = intval($resCreate->data);

        // Return created record
        $resRead = $this->account->Read([
            'account_id' => $accountID
        ]);

        // DB Error
        if(is_null($resRead->data)){
            return $resRead;
        }
        
        // Try uploaing personal photo if sent
        $uploadError = AttachmentService::UploadPersonalPhoto($accountID);
        
        $this->SendSignupEmail($accountID);
        
        // Notify all admins
        $account = $resRead->data[0];
         // Get admins
         $resList = $this->account->List([
            'account_type' => 'Admin',
            'limit' => 999
        ]);

        $notifs = [];
        foreach($resList->data as $acc){
            $notifs[] = [
                $acc['account_id'],
                0,
                'A new {account_type} account had been created for {name}',
                [
                    'account_type' => $account['account_type'], 
                    'name' => $account['name'] . ' ' . $account['surname']
                ],
                Router::routeUrl('accounts-manager-ids-view', ['ids' => $account['account_id']], '{LOCALE}')
            ];
        }

        if(!empty($notifs)){
            NotificationService::SendMultiple($notifs);
        }

        return new Result(
            $resRead->data,
            App::loc('{object} created', '', ['object' => 'Account']) . "\n" . App::loc($uploadError),
            $uploadError?'warning':'success',
            '',
            $resRead->metaData
        );
    }

    // Guard: Admin, User
    public function ReadMyAccount(array $routeParams = []): Result
    {
        $routeParams['account_id'] = Auth::getUser('account_id');

        $resRead = $this->Read($routeParams);

        if(!empty($resRead->data)){
            unset($resRead->data[0]['admin_notes']);
        }

        // Hide mobile verification code when it's supposed to be sent as SMS
        if(MOBILE_VERIFICATION_MODE == 'Send'){
            $resRead->data[0]['mobile_verification'] = '';
        }
        
        return $resRead;
    }

        // Guard: Admin, User
        public function ReadProfile(array $routeParams = []): Result
        {
            $routeParams['account_id'] = $routeParams['account_id']??0;
    
            // Get account general info
            $resRead = $this->Read($routeParams);

            // DB Error
            if(is_null($resRead->data)){
                return $resRead;
            }

            // Hide admin notes and remarks for none admin accounts
            if(!empty($resRead->data) && Auth::getUser('account_type') != "Admin"){
                unset($resRead->data[0]['admin_notes'], $resRead->data[0]['remarks']);
            }
            
            $ratingDescriptions = [
                App::loc('Unspecified'),
                App::loc('Bad'),
                App::loc('Okay'),
                App::loc('Good'),
                App::loc('Very good'),
                App::loc('Excellent')
            ];
    
            $resRead->data = array_map(function($item)use($ratingDescriptions){
                $item['rating_description'] = $ratingDescriptions[intval($item['rating'])]?? '';
                return $item;
            }, $resRead->data);
    
            return $resRead;
        }
    
    // Guard: Admin
    public function Read(array $routeParams = []): Result
    {
        $isSingleRecord = false;
        $message = '';
        $messageType = '';

        if(array_key_exists('page', $routeParams) && $routeParams['page'] > 0){
            $this->search['page'] = $routeParams['page'];
        };

        if(array_key_exists('account_id', $routeParams) && $routeParams['account_id'] > 0){
            if(count(explode(',', (string)$routeParams['account_id'])) > 1){
                $this->search = ['ids' => DB::sanitizeInParam($routeParams['account_id'])];
            }else{
                $this->search = ['account_id' => $routeParams['account_id']];
                $isSingleRecord = true;
            }
            
        };

        $resRead = $this->account->Read($this->search);

        // DB Error
        if(is_null($resRead->data)){
            return $resRead;
        }

        if($isSingleRecord && empty($resRead->data)){
            $message = App::loc('{object} not found', '', ['object' => 'Account']);
            $messageType = 'info';
        }

        // Encrypt account photo path
        $timeSalt = ',' . time();
        $resRead->data = array_map(function ($item)use($timeSalt){
            $item['personal_photo'] = Crypto::Encrypt($item['personal_photo'] . $timeSalt);
            return $item;
        }, $resRead->data);

        return new Result(
            $resRead->data,
            $message,
            $messageType,
            '',
            $resRead->metaData
        );
    }

    // Guard: Admin
    public function List(array $routeParams = []): Result
    {
        $resList = $this->account->List($this->search);

        // DB Error
        if(is_null($resList->data)){
            return $resList;
        }

        // Encrypt account photo path
        $timeSalt = ',' . time();
        $resList->data = array_map(function ($item)use($timeSalt){
            $item['personal_photo'] = Crypto::Encrypt($item['personal_photo'] . $timeSalt);
            return $item;
        }, $resList->data);

        return $resList;
    }

    // Guard: Admin, User
    public function UpdateMyAccount(array $routeParams = []): Result
    {
        $this->data['account_id'] = Auth::getUser('account_id');
        
        $resAccount = $this->account->Read(['account_id' => $this->data['account_id']]);
        if(empty($resAccount->data)){
            return new Result(
                null,
                App::loc('{object} not found', '', ['object' => 'Account']),
                'error'
            );
        }

        $account = $resAccount->data[0];

        $isEmailChanged = false;
        $isMobileChanged = false;

        $this->data['account_status'] = $account['account_status'];

        // Check if email, mobile or photo have been changed
        if($account['email'] != $this->data['email']){
            $isEmailChanged = true;
            $this->data['account_status'] = 'Verifying';
        }
        
        if($account['mobile'] != $this->data['mobile']){
            $isMobileChanged = true;
            $this->data['account_status'] = 'Verifying';
        }

        // Don't let normal user to change these properites
        $this->data['account_type'] = $account['account_type'];
        $this->data['gender'] = $account['gender'];
        $this->data['email_verification'] = $account['email_verification'];
        $this->data['mobile_verification'] = $account['mobile_verification'];
        $this->data['personal_photo_verification'] = $account['personal_photo_verification'];
        $this->data['remarks'] = $account['remarks'];
        
        if($isMobileChanged){         
            // Generate a new mobile verification code if verification mode is Receive
            if(MOBILE_VERIFICATION_MODE == 'Receive'){   
                $this->data['mobile_verification'] = AccountService::generateMobileVerification();
            }
        }

        $resUpdate = $this->Update($routeParams);
        
        // Check if email, mobile or photo have been changed
        if($isEmailChanged){
            $resVerifyEmail = $this->SendVerificationEmail();
        }

        if($isMobileChanged){         
            // Send verification SMS if verification mode is Send
            if(MOBILE_VERIFICATION_MODE == 'Send'){   
                $resVerifySMS = $this->SendVerificationSMS();
            }
        }

        $verifyMessage = [];
        $verifyMessage[] = $resUpdate->message;

        if(!is_null($resUpdate->data)){
            if(isset($resVerifyEmail)){
                $verifyMessage[] = $resVerifyEmail->message;
            }

            if(isset($resVerifySMS)){
                $verifyMessage[] = $resVerifySMS->message;
                $resUpdate->messageType = $resVerifySMS->messageType;
            }
        }

        $resUpdate->message = implode("\n", $verifyMessage);

        // Redirect to mobile verifying view if mobile number changed
        if($isMobileChanged && $resUpdate->messageType == 'success'){
            $resUpdate->redirect = Router::routeUrl('verify-my-mobile-view');
        }

        return $resUpdate;
    }

    // Guard: Admin
    public function Update(array $routeParams = []): Result
    {
        if($dataErr = $this->validator->validateOnly(['account_id', 'account_type', 'gender', 'hidden_personality', 'name', 'surname'])){
            return new Result(
                $dataErr,
                App::loc('Some data are missing or invalid'),
                'validation_error'
            );
        }

        $resDuplicate = $this->account->isDuplicated([
            'account_id' => $this->data['account_id'],
            'email' => $this->data['email'],
            'mobile' => $this->data['mobile']
        ]);

        // DB Error
        if(is_null($resDuplicate->data)){
            return $resDuplicate;
        }

        // Existing record found
        if($resDuplicate->data > 0){
            return new Result(
                null,
                App::loc('{object} exists with ID #{id}', '', ['object' => 'Account', 'id' => $resDuplicate->data]),
                'error'
            );
        }

        // Getting account data
        $account = $this->Read(['account_id' => $this->data['account_id']])->data[0];
        $this->data['account_status'] = $account['account_status'];

        // Check if personal photo included in the request
        $uploadError = '';
        $isPhotoChanged = false;

        if(Request::isFileSubmitted('personal_photo')){
            // Delete old personal photo (DB record and file)
            AttachmentService::DeleteByAccount(intval($this->data['account_id']), 'PersonalPhoto' );
            
            // Upload new photo
            $uploadError = AttachmentService::UploadPersonalPhoto(intval($this->data['account_id']));
            
            if(empty($uploadError)){
                $isPhotoChanged = true;

                // Personal photo must be re-verified
                $this->data['personal_photo_verification'] = '';
                
                // Notify all admins
                if($account['account_type'] != 'Admin'){
                    $this->data['account_status'] = 'Verifying';

                    // Get admins
                    $resList = $this->account->List([
                        'account_type' => 'Admin',
                        'limit' => 999
                    ]);

                    $notifs = [];
                    foreach($resList->data as $acc){
                        $notifs[] = [
                            $acc['account_id'],
                            0,
                            '{name} the owner of account #{id} has changed his personal photo' . ($account['gender'] == 'F'?'[1]':''),
                            [
                                'name' => $account['name'] . ' ' . $account['surname'],
                                'id' => $account['account_id']
                            ],
                            Router::routeUrl('accounts-manager-ids-view', ['ids' => $account['account_id']], '{LOCALE}')
                        ];
                    }

                    // Notify user
                    $notif = 'Your personal photo has been changed and It will be verified within 24 hours';

                    $notifs[] = [
                        $account['account_id'],
                        0,
                        $notif,
                        [],
                        Router::routeUrl('my-profile-view', [], '{LOCALE}')
                    ];

                    if(!empty($notifs)){
                        NotificationService::SendMultiple($notifs);
                    }

                    // Send email notification to user
                    if($account['notification_emails'] == 1){
                        $subject = App::loc('Your account needs verification', $account['preferred_language'], []);

                        MailService::Send(
                            $account['email'],
                            $subject,
                            'email-notification', 
                            [
                                'name' => $account['name'],
                                'notification' => App::loc($notif, $account['preferred_language']),
                                'notification_link' => WEBSITE_URL . Router::routeUrl('my-profile-view', [], $account['preferred_language'], false)
                            ],
                            true,
                            0,
                            $account['preferred_language']
                        );
                    }
                }
            }
        }

        // If account status set as Active by admin then update all verification status
        if($this->data['account_status'] == 'Active'){
            $this->data['email_verification'] = 'Verified';
            $this->data['mobile_verification'] = 'Verified';
            $this->data['personal_photo_verification'] = 'Verified';
        }else{
            // Set correct account status
            if($account['account_status'] == 'Verifying' && $account['email_verification'] == 'Verified' && $account['mobile_verification'] == 'Verified' && $this->data['personal_photo_verification'] == 'Verified'){
                $this->data['account_status'] = 'Active';
            }else if(!in_array($account['account_status'], ['Suspended', 'Deleted']) && ($account['email_verification'] != 'Verified' || $account['mobile_verification'] != 'Verified' || $this->data['personal_photo_verification'] != 'Verified')){
                $this->data['account_status'] = 'Verifying';
            }
        }

        $resUpdate = $this->account->Update([
            'account_id' => $this->data['account_id'],
            'account_type' => $this->data['account_type'],
            'gender' => $this->data['gender'],
            'hidden_personality' => $this->data['hidden_personality'],
            'name' => $this->data['name'],
            'surname' => $this->data['surname'],
            'country_code' => $this->data['country_code'],
            'email' => $this->data['email'],
            'email_verification' => $this->data['email_verification'],
            'mobile' => $this->data['mobile'],
            'mobile_verification' => $this->data['mobile_verification'],
            'password' => $this->data['password'],
            'preferred_language' => $this->data['preferred_language'],
            'notification_emails' => $this->data['notification_emails'],
            'personal_photo_verification' => $this->data['personal_photo_verification'],
            'admin_notes' => $this->data['admin_notes'],
            'account_status' => $this->data['account_status'],
            'remarks' => $this->data['remarks']
        ]);

        // DB Error
        if(is_null($resUpdate->data)){
            return $resUpdate;
        }

        // Return created record
        $resRead = $this->account->Read([
            'account_id' => $this->data['account_id']
        ]);

        // DB Error
        if(is_null($resRead->data)){
            return $resRead;
        }

        // Update current logged in account
        if(Auth::getUser('account_id') == $this->data['account_id']){
            Auth::setUser($resRead->data[0]);
        }

        // Notifiy user if account status has been changed
        $status = $resRead->data[0]['account_status'];

        if($account['account_type'] != 'Admin' && $account['account_status'] != $status){
            $accountID = intval($account['account_id']);
            
            if($status == 'Verifying'){
                // Notification and email are already sent if a personal photo uploaded
                if(!$isPhotoChanged){
                    $notif = 'A change has been made to your profile which requires re-verification of your account,A response will be given within a maximum of 24 hours';

                    NotificationService::Send($accountID, 0, $notif, [], Router::routeUrl('my-profile-view', [], '{LOCALE}'));

                    // Send email notification to user
                    if($account['notification_emails'] == 1){
                        $subject = App::loc('Your account needs verification', $account['preferred_language'], []);
                
                        MailService::Send(
                            $account['email'],
                            $subject,
                            'email-notification', 
                            [
                                'name' => $account['name'],
                                'notification' => App::loc($notif, $account['preferred_language']),
                                'notification_link' => WEBSITE_URL .  Router::routeUrl('my-profile-view', [], $account['preferred_language'], false)
                            ],
                            true,
                            0,
                            $account['preferred_language']
                        );
                    }
                }
            }else{
                $notif = 'Your account status has been changed to {status}';
                $notifParams = [
                    'status' => $status
                ];

                NotificationService::Send($accountID, 0, $notif, $notifParams, Router::routeUrl('my-profile-view', [], '{LOCALE}'));

                // Send email notification to user
                if($account['notification_emails'] == 1){
                    $subject = App::loc('Your account status changed', $account['preferred_language'], []);
                    
                    MailService::Send(
                        $account['email'],
                        $subject,
                        'email-notification', 
                        [
                            'name' => $account['name'],
                            'notification' => App::loc($notif, $account['preferred_language'], $notifParams),
                            'notification_link' => WEBSITE_URL . Router::routeUrl('my-profile-view', [], $account['preferred_language'], false)
                        ],
                        true,
                        0,
                        $account['preferred_language']
                    );
                }
            }
        }

        return new Result(
            $resRead->data,
            App::loc('{object} updated', '', ['object' => 'Account']) . "\n" . App::loc($uploadError),
            $uploadError?'warning':'success',
            '',
            $resRead->metaData
        );
    }

    // Guard: Admin, User
    public function DeleteMyAccount(array $routeParams = []): Result
    {
        $this->data['account_id'] = Auth::getUser('account_id');

        $resDelete = $this->Delete($routeParams);

        // Logoout
        $this->Logout([]);

        $resDelete->message = App::loc("It's time to say goodbye,We'll be missing you");
        $resDelete->redirect = Router::routeUrl('home-view');

        return $resDelete;
    }

    // Guard: Admin
    public function Delete(array $routeParams = []): Result
    {
        $resReferenced = $this->account->isReferenced([
            'account_id' => $this->data['account_id']
        ]);

        if(!empty($resReferenced->data)){
            $resReferenced->data = array_map(function($item){
                $item['model'] = App::loc($item['model']);
                return $item;
            }, $resReferenced->data);
            
            return new Result(
                $resReferenced->data,
                App::loc('{object} is referenced by', '', ['object' => 'Account']),
                'reference_error',
                ''
            );
        }

        $accountID = intval($this->data['account_id']);

        // Delete all account related attachments
        AttachmentService::DeleteByAccount($accountID, '');
        
        $resDelete = $this->account->Delete(['account_id' => $accountID]);

        // DB Error
        if(is_null($resDelete->data)){
            return new Result(
                null,
                App::loc('Failed to delete {object}', '',['object' => 'Account']),
                'error',
                ''
            );
        }
        
        return new Result(
            $this->data['account_id'],
            App::loc('{object} deleted', '', ['object' => 'Account']),
            'success',
            ''
        );
    }

    // Guard: Anonymous
    public function Login(Array $routeParams=[]){
        $this->validator->add('email_mobile', App::loc('Invalid {field}', '', ['field' => 'Email or mobile number']), function(){
            return ValidationRule::email()($this->data['email_mobile']) || ValidationRule::regexp('#[+0]?[0-9\s]{4,12}#')($this->data['email_mobile']);
        });

        if($dataErr = $this->validator->validateOnly(['email_mobile'])){
            return new Result(
                $dataErr,
                App::loc('Some data are missing or invalid'),
                'validation_error'
            );
        }

        $this->validator->remove('email_mobile');

        // If mobile number is used, remove country dialing code prefix (+, 0)
        $number = trim($this->data['email_mobile']);
        
        if(!ValidationRule::email()($number)){
            $number = ltrim($number, "+0");
            $this->data['email_mobile'] = $number;
        }

        $resLogin = $this->account->Login([
            'email_mobile' => $this->data['email_mobile'],
            'password' => $this->data['password']
        ]);
   
        // DB Error
        if(is_null($resLogin->data)){
            return $resLogin;
        }

        if ($resLogin->data === false || empty($resLogin->data)) {
            return new Result(
                [],
                App::loc('Login failed'),
                'error',
                ''
            );
        }

        $accountData = $resLogin->data[0];

        // Check account status
        if(in_array($accountData['account_status'], ['Pending', 'Suspended', 'Deleted'])){
            return new Result(
                $accountData,
                App::loc('Your account is {status}', '', ['status' => $accountData['account_status']]),
                'error',
                ''
            );
        }

        Auth::setUser($resLogin->data[0]);

        $requestUrl = Router::getRedirectRouteName();

        if(empty($requestUrl)){
            $requestUrl = Router::routeUrl(Auth::getUser('account_type') == 'Admin'? 'dashboard-view': 'home-view');
        }
        
        return new Result(
            $resLogin->data,
            App::loc("Welcome {name}", '', ['name' => Auth::getUser('name')]),
            'success',
            $requestUrl
        );
    }

    // Guard: Admin, User
    public function Logout(Array $routeParams=[]){
        if(!Auth::authenticated()){
            return new Result(
                null,
                APP::loc("You are not logged in"),
                'error',
                Router::routeUrl('home-view')
            );
        }

        $name = Auth::getUser('name');
        Auth::destroyUser();

        return new Result(
            null,
            APP::loc("Good Bye {name}", '', ['name' => $name]),
            'success',
            Router::routeUrl('home-view')
        );
    }

    // Guard: Admin, User
    public function Photo(Array $routeParams=[]){
        $photoPath = $routeParams['photo_path']??0;
        
        if($photoPath == '0'){
            $photoPath = Auth::getUser('personal_photo');
        }else{
            // Decrypt photoPath
            $photoPath = Crypto::Decrypt($photoPath);
            $arr = explode(',', $photoPath);
            $photoPath = $arr[0];
            $time = $arr[1]??0;

            // Inavlidate images after specified amount of seconds
            if(time() - $time > IMAGE_INVALIDATION_PERIOD){
               $photoPath = 'hidden';
            }
        }
 
        if(empty($photoPath)){
            $photoPath = BASE_DIR . '/img/user.png';
            header('Content-Type: image/png');

        }elseif($photoPath == 'hidden'){
            $photoPath = BASE_DIR . '/img/hidden.png';
            header('Content-Type: image/png');

        }elseif(file_exists(UPLOAD_DIR . $photoPath)){
            $photoPath = UPLOAD_DIR . $photoPath;
            header('Content-Type: image/jpeg');
            
        }else{
            $photoPath = BASE_DIR . '/img/user.png';
            header('Content-Type: image/png');
        }

		header('Content-Disposition: inline');
		header('Content-Length: '.filesize($photoPath));
		
		// Write directly to the ouput buffer		   
		readfile($photoPath);
    }

    // Guard: Admin, User
    public function SendMeVerificationEmail(Array $routeParams=[]){

        $this->data['account_id'] = Auth::getUser('account_id');
        return $this->SendVerificationEmail();
    }

    // Guard: Admin, User
    public function SendMeVerificationSMS(Array $routeParams=[]){
        $this->data['account_id'] = Auth::getUser('account_id');

        return $this->SendVerificationSMS();

    }

    private function SendSignupEmail(int $accountID){
        $resRead = $this->Read(['account_id' => $accountID]);

        if(is_null($resRead->data) || empty($resRead->data)){
            return new Result(
                null,
                App::loc('{object} not found', '', ['object' => 'Account']),
                'error',
                ''
            );
        }

        $account = $resRead->data[0];
        $email = $account['email'];
        $subject = App::loc('Your account is created');
        
        // Encrypt account id with verification code to be used in verification link
        $emailVerificationCode = Crypto::Encrypt($accountID . ';' . $account['email_verification']);

        $params = [
            'name' => $account['name'],
            'country' => $account['country'],
            'email' => $account['email'],
            'mobile' => $account['mobile'],
            'register_date' => $account['register_date'],
            'email_verification_view_url' => WEBSITE_URL  . Router::routeUrl('verify-my-email-view', ['verification_code' => $emailVerificationCode], $account['preferred_language'], false),
            'login_view_url' => WEBSITE_URL . Router::routeUrl('login-view', [], $account['preferred_language'], false), 
            'my_profile_view_url' => WEBSITE_URL . Router::routeUrl('my-profile-view', [], $account['preferred_language'], false),
            'my_cars_view_url' => WEBSITE_URL . Router::routeUrl('my-cars-view', [], $account['preferred_language'], false),
            'verification_period' => 15
        ];
        
        // Switch email template according to mobile verification mode
        if(MOBILE_VERIFICATION_MODE == 'Send'){
            $tplEmail = 'email-signup-send';
            $params['mobile_verification_view_url'] = WEBSITE_URL . Router::routeUrl('verify-my-mobile-view', [], $account['preferred_language'], false);
        }elseif(MOBILE_VERIFICATION_MODE == 'Receive'){
            $tplEmail = 'email-signup-receive';
            $params['mobile_verification_code'] = $account['mobile_verification'];
            $params['sms_virtual_number'] = SMSService::GetVirtualNumber();
        }

        MailService::Send(
            $email,
            $subject,
            $tplEmail,
            $params,
            true,
            0,
            $account['preferred_language']
        );
    }

    // Guard: Admin
    public function SendVerificationEmail(Array $routeParams=[]){
        $accountID = intval($this->data['account_id']??0);

        $resRead = $this->Read(['account_id' => $accountID]);

        if(is_null($resRead->data) || empty($resRead->data)){
            return new Result(
                null,
                App::loc('{object} not found', '', ['object' => 'Account']),
                'error',
                ''
            );
        }

        $account = $resRead->data[0];
        $email = $account['email'];
        $verificationCode = AccountService::generateEmailVerification($email);

        $this->account->UpdateEmailVerification([
            'account_id' => $accountID,
            'email_verification' => $verificationCode,
            'account_status' => 'Verifying'
        ]);

        // Encrypt account id with verification code to be used in verification link
        $verificationCode = Crypto::Encrypt($accountID . ';' . $verificationCode);

        $subject = App::loc('Please verify your email', $account['preferred_language']);

        $params = [
            'name' => $account['name'],
            'email_verification_view_url' => WEBSITE_URL . Router::routeUrl('verify-my-email-view', ['verification_code' => $verificationCode], $account['preferred_language'], false),
        ];

        MailService::Send(
            $email,
            $subject,
            'email-verification',
            $params,
            true,
            0,
            $account['preferred_language']
        );

        return new Result(
            null,
            App::loc("Verification message was sent to your email address,Please check and follow the instructions"),
            'success',
            ''
        );
    }

    // Guard: Admin
    public function SendVerificationSMS(Array $routeParams=[]){
        $accountID = $this->data['account_id']??0;

        if($accountID == 0){
            return new Result(
                null,
                App::loc('{object} not found', '', ['object' => 'Account']),
                'error',
                ''
            );
        }

        $account = $this->Read(['account_id' => $accountID])->data[0];
        $verificationCode = AccountService::generateMobileVerification();

        // E.164 number format is required by the SMS service (no +-() leading 0 or spaces)
        $mobile = str_replace([' ', '+', '-', '(', ')'] , '', ltrim($account['dialing_code'], '0') . ltrim($account['mobile'], '0'));

        // Call SMS API
        $resSMS = SMSService::Send($mobile, App::loc('Verification code from {website} is {code}', '', ['website' => App::loc(WEBSITE_TITLE, $account['preferred_language']), 'code' => $verificationCode]));

        $message = App::loc("Verification SMS was sent to your mobile number,Please check and follow the instructions");
        
        if($resSMS->messageType != 'success'){
            $message = App::loc("For some reason we were unable to send the verification code to your mobile number,Please contact the admin");
        }

        // Keep the status pending when account is not yet verified by email
        $accountStatus = ($account['account_status'] == 'Pending')?'Pending': 'Verifying';

        $this->account->UpdateMobileVerification([
            'account_id' => $accountID,
            'mobile_verification' => $verificationCode,
            'account_status' => $accountStatus
        ]);

        return new Result(
            null,
            $message,
            $resSMS->data == true? 'success': 'warning',
            ''
        );
    }
    
    // Guard: Anonymous
    public function VerifyMyEmail(Array $routeParams=[]): Result{
        $accountID = 0;
        $verificationCode = $routeParams['verification_code']??'';

        // Decrypt verification code
        if(!empty($verificationCode)){
            $verificationCode = Crypto::Decrypt($verificationCode);

            // Extract account id
            $data = explode(';', $verificationCode, 2);
            
            if(count($data) < 2){
                return new Result(
                    [],
                    App::loc('Invalid {field}', '', ['field' => 'verification code']),
                    'error'
                );
            }

            [$accountID, $verificationCode] = $data;
        }

        $this->data['account_id'] = $accountID;

        if($dataErr = $this->validator->validateOnly(['account_id'])){
            return new Result(
                $dataErr,
                App::loc('Some data are missing or invalid'),
                'error'
            );
        }

        $resRead = $this->Read(['account_id' => $accountID]);

        if(is_null($resRead->data) || empty($resRead->data)){
            return new Result(
                [],
                App::loc('{object} not found', '', ['object' => 'Account']),
                'error'
            );
        }

        $account = $resRead->data[0];

        // Check if account is suspended or deleted
        if(in_array($account['account_status'], ['Suspended', 'Deleted'])){
            return new Result(
                $account,
                App::loc("Sorry {name},Your account is {status},Please check with admins", '', [
                    'name' => $account['name'] . ' ' . $account['surname'],
                    'status' => $account['account_status']
                ]),
                'error'
            );
        }

        if($account['email_verification'] == 'Verified'){
            return new Result(
                $account,
                App::loc("This email has already been verified,Thanks {name}", '', ['name' => $account['name'] . ' ' . $account['surname']]),
                'info'
            );
        }

        if($verificationCode != $account['email_verification']){
            return new Result(
                $account,
                App::loc("Sorry {name},We couldn't recognize your verification code", '', ['name' => $account['name'] . ' ' . $account['surname']]),
                'error'
            );
        }

        // Set correct account status
        $accountStatus = 'Verifying';

        if($account['mobile_verification'] == 'Verified' && $account['personal_photo_verification'] == 'Verified'){
            $accountStatus = 'Active';
        }

        $resUpdate = $this->account->UpdateEmailVerification([
            'account_id' => $accountID,
            'email_verification' => 'Verified',
            'account_status' => $accountStatus
        ]);

        if(is_null($resUpdate->data)){
            return $resUpdate;
        }

        // Update user authentication session if logged in
        $auth = Auth::getUser();
        
        if(!empty($auth)){
            $auth['account_status'] = $accountStatus;
            Auth::setUser($auth);
        }

        return new Result(
            $account,
            App::loc("Thank you {name},Your email address has been verified successfully", '',  ['name' => $account['name'] . ' ' . $account['surname']]),
            'success'
        );
    }

    // Guard: Admin, User
    public function VerifyMyMobile(Array $routeParams=[]): Result{ 
        $accountID = intval(Auth::getUser('account_id')??0);
        $this->data['account_id'] = $accountID;

        if($dataErr = $this->validator->validateOnly(['account_id'])){
            return new Result(
                $dataErr,
                App::loc('Some data are missing or invalid'),
                'validation_error'
            );
        }

        $verificationCode = $this->data['verification_code']??'';

        $resRead = $this->Read(['account_id' => $this->data['account_id']]);

        if(is_null($resRead->data)){
            return new Result(
                [],
                App::loc('{object} not found', '', ['object' => 'Account']),
                'error'
            );
        }

        $account = $resRead->data[0];
        $fullName = $account['name'] . ' ' . $account['surname'];

        // Check if account is suspended or deleted
        if(in_array($account['account_status'], ['Suspended', 'Deleted'])){
            return new Result(
                $account,
                App::loc("Sorry {name},Your account is {status},Please check with admins", '', [
                    'name' => $fullName,
                    'status' => $account['account_status']
                ]),
                'error'
            );
        }

        if($account['mobile_verification'] == 'Verified'){
            return new Result(
                $account,
                App::loc("This mobile has already been verified,Thanks {name}", '', ['name' => $fullName]),
                'info'
            );
        }

        if($verificationCode != $account['mobile_verification']){
            return new Result(
                $account,
                App::loc("Sorry {name},We couldn't recognize your verification code", '', ['name' => $fullName]),
                'error'
            );
        }

        // Set correct account status
        $accountStatus = 'Verifying';

        if($account['email_verification'] == 'Verified' && $account['personal_photo_verification'] == 'Verified'){
            $accountStatus = 'Active';
        }

        // Set mobile verification status       
        $this->account->UpdateMobileVerification([
            'account_id' => $accountID,
            'mobile_verification' => 'Verified',
            'account_status' => $accountStatus
        ]);

        // Update user authentication session
        $auth = Auth::getUser();
        $auth['account_status'] = $accountStatus;

        Auth::setUser($auth);

        return new Result(
            $account,
            App::loc("Thank you {name},Your mobile number has been verified successfully", '',  ['name' => $fullName]),
            'success',
            Router::routeUrl('home-view')
        );
    }

    public function VerifyMyMobileWebhook(Array $routeParams=[]){
        $payload = array_merge(Request::body(), Request::getQueryParams());

        // Ignore empty payloads
        if(count($payload) == 0){
            return SMSService::GetSuccessResponse();
        }
        
        // Validate webhook signature and get the payload back
        $resp = SMSService::ValidateWebhook($payload);

        if($resp->messageType != 'success'){
            // We can't notify the user since this request is anonymous
            return SMSService::GetSuccessResponse();
        }

        $sms = $resp->data;
        // Find account by mobile number
        $smsType = $sms['type']??'';
        if(!in_array($smsType, ['text', 'unicode'])){
            // We can't notify the user since this request is anonymous
            return SMSService::GetSuccessResponse();
        }

        $mobile = $sms['from']??'';
        $verificationCode = $sms['text']??'';

        // E.164 Number format is required, Adding + to match country dialing code pattern
        $mobile = '+' . str_replace([' ', '+', '-', '(', ')'] , '', ltrim($mobile, '0'));

        // Get account by mobile number prefixed with country code
        $resRead = $this->account->Read(['country_mobile' => $mobile]);

        $msg = '';
        $params = [];

        if(is_null($resRead->data)){
            // We can't notify the user since this request is anonymous
            return SMSService::GetSuccessResponse();
        }
            
        $account = $resRead->data[0];
        $params = ['name' => $account['name'] . ' ' . $account['surname']];

        // Check if account is suspended
        if(in_array($account['account_status'], ['Suspended', 'Deleted'])){
            $params['status'] = $account['account_status'];
            $msg ="Sorry {name},Your account is {status},Please check with admins";
        
        }elseif($account['mobile_verification'] == 'Verified'){
            $msg = "This mobile has already been verified,Thanks {name}";
        
        }elseif($verificationCode != $account['mobile_verification']){
            $msg ="Sorry {name},We couldn't recognize your verification code";
        }

        if($msg){
            // Send error notification to the user
            $notifLink = Router::routeUrl('my-profile-view', [], '{LOCALE}');

            NotificationService::Send(intval($account['account_id']), 0, $msg, $params, $notifLink);
            return SMSService::GetSuccessResponse();
        }

        // Set correct account status
        $accountStatus = 'Verifying';

        if($account['email_verification'] == 'Verified' && $account['personal_photo_verification'] == 'Verified'){
            $accountStatus = 'Active';
        }

        // Set mobile verification status       
        $this->account->UpdateMobileVerification([
            'account_id' => $account['account_id'],
            'mobile_verification' => 'Verified',
            'account_status' => $accountStatus
        ]);

        // Send success notification to the user
        $notifLink = Router::routeUrl('my-profile-view', [], '{LOCALE}');
        $msg = "Thank you {name},Your mobile number has been verified successfully";
        
        NotificationService::Send(intval($account['account_id']), 0, $msg, $params, $notifLink);

        SMSService::GetSuccessResponse();
    }

    // Guard: Admin, User
    public function Unsubscribe(array $routeParams = []): Result
    {
        $accountID = Auth::getUser('account_id');

        $resUpdate = $this->account->Unsubscribe([
            'account_id' => $accountID,
            'notification_emails' => $this->data['notification_emails'],
        ]);

        // DB Error
        if(is_null($resUpdate->data)){
            return $resUpdate;
        }

        return new Result(
            true,
            App::loc('Email subscription options updated'),
            'success'
        );
    }
}
