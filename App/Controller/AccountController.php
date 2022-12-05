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
            'role' => 'User', // User, Admin
            'gender' => 'M', // M, F
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'email_verification' => '',
            'mobile' => '',
            'password' => '',
            'password_reset_code' => '',
            'preferred_language' => 'en',
            'personal_photo' => '',
            'register_date' => date('Y-m-d H:i:s'),
            'admin_notes' => '',
            'account_status' => 'Pending', // Pending, Warned, Verifiying, Active, Suspended, Deleted
            'remember_me' => 0,
            'all_devices' => 0,
        ]);
        // Get search params from both request body and query string
        $this->search = array_merge(json_decode($data['search'] ?? '[]', true), json_decode(Request::getQueryParams()['search'] ?? '[]', true));
        
        // Only hash password if not empty
        if(!empty($data['password'])){
            $data['password'] = hash('sha256', $data['password']);
        }

        $this->data = $data;

        $this->validator = new Validator($this->data);
        $this->validator->add('account_id', 'Invalid account id', ValidationRule::number(1));
        $this->validator->add('role', 'Invalid account role', ValidationRule::inList(['User', 'Admin']));
        $this->validator->add('gender', 'Invalid gender', ValidationRule::inList(['M', 'F']));
        $this->validator->add('first_name', 'first_name is missing or inavlid', ValidationRule::string(3, 25));
        $this->validator->add('last_name', 'last_name is missing or inavlid', ValidationRule::string(3, 25));
        $this->validator->add('email', 'Invalid email', ValidationRule::email());
        $this->validator->add('password', 'Password is missing', ValidationRule::notEmpty());
        $this->validator->add('preferred_language', 'Invalid language', ValidationRule::inList(['en', 'ar']));
        $this->validator->add('account_status', 'Invalid account status', ValidationRule::inList(['Pending', 'Warned', 'Verifying', 'Active', 'Suspended', 'Deleted']));
    }

    // Guard: Anonymous
    public function SignUp(array $routeParams = []): Result
    {
        $this->data['role'] = 'User';
        $this->data['account_status'] = 'Pending';
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

            Auth::setUser($tempUserData);

            // Redirect to Thank you page
            $result->redirect = Router::routeUrl('thank-you-view');
            $result->message = "\n" . App::loc('Thanks for signing up to {website}', $account['preferred_language'], ['website' => App::loc(WEBSITE_TITLE, $account['preferred_language'])]);
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

        // If account status set as Active by admin then update email verification status
        $this->data['email_verification'] = $emailVerificationCode;

        if($this->data['account_status'] == 'Active'){
            $this->data['email_verification'] = 'Verified';
        }

        $resCreate = $this->account->Create([
            'role' => $this->data['role'],
            'gender' => $this->data['gender'],
            'first_name' => $this->data['first_name'],
            'last_name' => $this->data['last_name'],
            'email' => $this->data['email'],
            'email_verification' => $this->data['email_verification'],
            'mobile' => $this->data['mobile'],
            'password' => $this->data['password'],
            'password_reset_code' => '',
            'preferred_language' => $this->data['preferred_language'],
            'register_date' => date('Y-m-d H:i:s'),
            'admin_notes' => $this->data['admin_notes'],
            'account_status' => $this->data['account_status'],
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
            'role' => 'Admin',
            'limit' => 999
        ]);

        $notifs = [];
        foreach($resList->data as $acc){
            $notifs[] = [
                $acc['account_id'],
                0,
                'A new {role} account had been created for {first_name}',
                [
                    'role' => $account['role'], 
                    'first_name' => $account['first_name'] . ' ' . $account['last_name']
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

        // Hide admin notes for none admin accounts
        if(!empty($resRead->data) && Auth::getUser('role') != "Admin"){
            unset($resRead->data[0]['admin_notes']);
        }

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

        $this->data['account_status'] = $account['account_status'];

        // Check if email, mobile or photo have been changed
        if($account['email'] != $this->data['email']){
            $isEmailChanged = true;
            $this->data['account_status'] = 'Verifying';
        }

        // Don't let normal user to change these properites
        $this->data['role'] = $account['role'];
        $this->data['gender'] = $account['gender'];
        $this->data['email_verification'] = $account['email_verification'];

        $resUpdate = $this->Update($routeParams);
        
        // Check if email, mobile or photo have been changed
        if($isEmailChanged){
            $resVerifyEmail = $this->SendVerificationEmail();
        }

        $verifyMessage = [];
        $verifyMessage[] = $resUpdate->message;

        if(!is_null($resUpdate->data)){
            if(isset($resVerifyEmail)){
                $verifyMessage[] = $resVerifyEmail->message;
            }
        }

        $resUpdate->message = implode("\n", $verifyMessage);

        return $resUpdate;
    }

    // Guard: Admin
    public function Update(array $routeParams = []): Result
    {
        if($dataErr = $this->validator->validateOnly(['account_id', 'role', 'gender', 'first_name', 'last_name'])){
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
        
        // Force account status set by an admin
        if(!App::isAdmin()){
            $this->data['account_status'] = $account['account_status'];
        }

        // Check if personal photo included in the request
        $uploadError = '';

        if(Request::isFileSubmitted('personal_photo')){
            // Delete old personal photo (DB record and file)
            AttachmentService::DeleteByAccount(intval($this->data['account_id']), 'PersonalPhoto' );
            
            // Upload new photo
            $uploadError = AttachmentService::UploadPersonalPhoto(intval($this->data['account_id']));
        }

        $this->data['account_status'] = $account['account_status'];

        // If account status set as Active by admin then update email verification status
        if($this->data['account_status'] == 'Active'){
            $this->data['email_verification'] = 'Verified';
        }else{
            // Set correct account status
            if($account['account_status'] == 'Verifying' && $account['email_verification'] == 'Verified'){
                $this->data['account_status'] = 'Active';
            }else if(!in_array($account['account_status'], ['Suspended', 'Deleted']) && ($account['email_verification'] != 'Verified')){
                $this->data['account_status'] = 'Verifying';
            }
        }

        $resUpdate = $this->account->Update([
            'account_id' => $this->data['account_id'],
            'role' => $this->data['role'],
            'gender' => $this->data['gender'],
            'first_name' => $this->data['first_name'],
            'last_name' => $this->data['last_name'],
            'email' => $this->data['email'],
            'email_verification' => $this->data['email_verification'],
            'mobile' => $this->data['mobile'],
            'password' => $this->data['password'],
            'admin_notes' => $this->data['admin_notes'],
            'account_status' => $this->data['account_status'],
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

        if($account['role'] != 'Admin' && $account['account_status'] != $status){
            $accountID = intval($account['account_id']);
            
            $notif = 'Your account status has been changed to {status}';
            $notifParams = [
                'status' => $status
            ];

            NotificationService::Send($accountID, 0, $notif, $notifParams, Router::routeUrl('my-profile-view', [], '{LOCALE}'));

            // Send email notification to user
            $subject = App::loc('Your account status changed', $account['preferred_language'], []);
            
            MailService::Send(
                $account['email'],
                $subject,
                'email-notification', 
                [
                    'first_name' => $account['first_name'],
                    'notification' => App::loc($notif, $account['preferred_language'], $notifParams),
                    'notification_link' => WEBSITE_URL . Router::routeUrl('my-profile-view', [], $account['preferred_language'], false)
                ],
                true,
                0,
                $account['preferred_language']
            );
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
    public function Login(Array $routeParams=[])
    {
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
            $message = App::loc('Your account is {status}', '', ['status' => $accountData['account_status']]);

            if($accountData['account_status'] == 'Pending'){
                $message = App::loc('Please check your email to complete the procedures first');
            }

            return new Result(
                $accountData,
                $message,
                'error',
                ''
            );
        }

        // Handle remember me feature
        $this->RememberMe($accountData);

        Auth::setUser($accountData);

        $requestUrl = Router::getRedirectRouteName();

        if(empty($requestUrl)){
            $requestUrl = Router::routeUrl('dashboard-view');
        }
        
        return new Result(
            $resLogin->data,
            App::loc("Welcome {name}", '', ['name' => Auth::getUser('name')]),
            'success',
            $requestUrl
        );
    }

    // Guard: Anonymous
    public function CookieLogin(Array $routeParams=[]): Result
    {
        [$lookup, $validator] = explode(':', $_COOKIE[REMEMBER_ME_COOKIE_NAME]);

        $resLogin = $this->account->CookieLogin([
            'lookup_token' => $lookup
        ]);
   
        // DB Error
        if(is_null($resLogin->data)){
            return $resLogin;
        }

        if($resLogin->data === false || empty($resLogin->data)) {
            return new Result(
                [],
                App::loc('Login failed'),
                'error',
                ''
            );
        }

        $accountData = $resLogin->data[0];

        // Check if validator_token is valid
        if($validator != Crypto::Decrypt($accountData['validator_token'])){
            // Clear current remember me token
            $this->SetRememberMeToken('', -1);

            return new Result(
                [],
                App::loc('Login failed'),
                'error',
                ''
            );
        }

        // Check account status
        if(in_array($accountData['account_status'], ['Pending', 'Suspended', 'Deleted'])){
            $message = App::loc('Your account is {status}', '', ['status' => $accountData['account_status']]);

            if($accountData['account_status'] == 'Pending'){
                $message = App::loc('Please check your email to complete the procedures first');
            }

            return new Result(
                $accountData,
                $message,
                'error',
                ''
            );
        }

        // Refresh remember me (update expiry date)
        $this->SetRememberMeToken("$lookup:$validator");

        Auth::setUser($accountData);

        return new Result(
            $resLogin->data,
            App::loc("Welcome {first_name}", '', ['first_name' => Auth::getUser('first_name')]),
            'success'
        );
    }

    // Internal use
    private function RememberMe(array $accountData):void
    {
        if(!ENABLE_REMEMBER_ME){
            return;
        }

        if($this->data['remember_me']??0){
            $lookup = '';
            $validator = '';

            // Generate login token if not exist
            if(empty($accountData['lookup_token'])){
                $lookup = base64_encode(random_bytes(9));
                $validator = base64_encode(random_bytes(18));

                $this->account->UpdateTokens([
                    'account_id' => $accountData['account_id'],
                    'lookup_token' => $lookup,
                    'validator_token' => Crypto::Encrypt($validator)
                ]);
            }else{
                $lookup = $accountData['lookup_token'];
                $validator = Crypto::Decrypt($accountData['validator_token']);
            }

            $this->SetRememberMeToken("$lookup:$validator");
        }else{
            // Delete current remember me cookie if not checked
            $this->SetRememberMeToken('', -1);
        }
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

        $first_name = Auth::getUser('first_name');
        Auth::destroyUser();

        return new Result(
            null,
            APP::loc("Good Bye {first_name}", '', ['first_name' => $first_name]),
            'success',
            Router::routeUrl('home-view')
        );
    }

    // Internal use
    private function SetRememberMeToken($token, $expiry = REMEMBER_ME_EXPIRE_DAYS):void{
        setcookie(
            REMEMBER_ME_COOKIE_NAME,
            $token,
            [
                'expires' => time() + 60 * 60 * 24 * $expiry,
                'path' => '/',
                'domain' => parse_url(WEBSITE_URL, PHP_URL_HOST),
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    // Guard: Anonymous
    public function RequestPasswordReset(Array $routeParams=[]): Result
    {
        if($dataErr = $this->validator->validateOnly(['email'])){
            return new Result(
                $dataErr,
                App::loc('Some data are missing or invalid'),
                'validation_error'
            );
        }
        
        // Check if email exists
        $resRead = $this->account->Read(['email' => $this->data['email']]);
         // DB Error
         if(is_null($resRead->data)){
            return $resRead;
        }

        if ($resRead->data === false || empty($resRead->data)) {
            return new Result(
                [],
                App::loc('Unrecognized email'),
                'error',
                ''
            );
        }

        $account = $resRead->data[0];

        // Generate password reset code
        $resetCode = AccountService::generatePasswordReset($account['email']);
        $resetUrl =  WEBSITE_URL . Router::routeUrl('reset-my-password-view', ['reset_code' => $resetCode], $account['preferred_language'], false);

        // Update reset code
        $resReset = $this->account->SetPasswordResetCode([
            'account_id' => $account['account_id'],
            'password_reset_code' => $resetCode
        ]);

        // DB Error
        if(is_null($resReset->data)){
            return $resReset;
        }

        // Send reset code via email
        MailService::Send(
            $account['email'],
            App::loc('Password reset code', $account['preferred_language']),
            'email-password-reset',
            [
                'first_name' => $account['first_name'],
                'password_reset_view_url' => $resetUrl
            ],
            true,
            0,
            $account['preferred_language']
        );

        return new Result(
            [],
            App::loc('Check your mailbox for password reset link'),
            'info',
            'login'
        );
    }

    // Guard: Anonymous
    public function ResetMyPassword(Array $routeParams=[]): Result
    {
        $this->validator->add('password_reset_code', App::loc('Invalid {field}', '', ['field' => 'Password reset code']), ValidationRule::notEmpty());

        if($dataErr = $this->validator->validateOnly(['password', 'password_reset_code'])){
            return new Result(
                $dataErr,
                App::loc('Some data are missing or invalid'),
                'validation_error'
            );
        }

        $this->validator->remove('password_reset_code');
        
        // Check if email exists
        $resRead = $this->account->Read(['password_reset_code' => $this->data['password_reset_code']]);
         // DB Error
         if(is_null($resRead->data)){
            return $resRead;
        }

        if ($resRead->data === false || empty($resRead->data)) {
            return new Result(
                [],
                App::loc('Invalid password reset code'),
                'error',
                ''
            );
        }

        $account = $resRead->data[0];

        // Update reset code
        $resReset = $this->account->SetNewPassword([
            'account_id' => $account['account_id'],
            'password' => $this->data['password']
        ]);

        // DB Error
        if(is_null($resReset->data)){
            return $resReset;
        }

        return new Result(
            [],
            App::loc('Your new password has been set'),
            'success',
            'login'
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
            $photoPath = BASE_DIR . '/../img/user.png';
            header('Content-Type: image/png');

        }elseif($photoPath == 'hidden'){
            $photoPath = BASE_DIR . '/../img/hidden.png';
            header('Content-Type: image/png');

        }elseif(file_exists(UPLOAD_DIR . $photoPath)){
            $photoPath = UPLOAD_DIR . $photoPath;
            header('Content-Type: image/jpeg');
            
        }else{
            $photoPath = BASE_DIR . '/../img/user.png';
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
            'first_name' => $account['first_name'],
            'email' => $account['email'],
            'mobile' => $account['mobile'],
            'register_date' => $account['register_date'],
            'email_verification_view_url' => WEBSITE_URL  . Router::routeUrl('verify-my-email-view', ['verification_code' => $emailVerificationCode], $account['preferred_language'], false),
            'login_view_url' => WEBSITE_URL . Router::routeUrl('login-view', [], $account['preferred_language'], false), 
            'my_profile_view_url' => WEBSITE_URL . Router::routeUrl('my-profile-view', [], $account['preferred_language'], false),
            'verification_period' => 15
        ];

        MailService::Send(
            $email,
            $subject,
            'email-signup',
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
            'first_name' => $account['first_name'],
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
                    App::loc('Invalid {field}', '', ['field' => 'Verification code']),
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
                App::loc("Sorry {first_name},Your account is {status},Please check with admins", '', [
                    'first_name' => $account['first_name'] . ' ' . $account['last_name'],
                    'status' => $account['account_status']
                ]),
                'error'
            );
        }

        if($account['email_verification'] == 'Verified'){
            return new Result(
                $account,
                App::loc("This email has already been verified,Thanks {first_name}", '', ['first_name' => $account['first_name'] . ' ' . $account['last_name']]),
                'info'
            );
        }

        if($verificationCode != $account['email_verification']){
            return new Result(
                $account,
                App::loc("Sorry {first_name},We couldn't recognize your verification code", '', ['first_name' => $account['first_name'] . ' ' . $account['last_name']]),
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
            App::loc("Thank you {first_name},Your email address has been verified successfully", '',  ['first_name' => $account['first_name'] . ' ' . $account['last_name']]),
            'success'
        );
    }

    // Guard: Admin, User
    public function Unsubscribe(array $routeParams = []): Result
    {
        $accountID = Auth::getUser('account_id');

        $resUpdate = $this->account->Unsubscribe([
            'account_id' => $accountID,
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
