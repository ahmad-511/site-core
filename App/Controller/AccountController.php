<?php
declare (strict_types = 1);
namespace App\Controller;

use App\Core\Controller;
use App\Core\App;
use App\Core\Auth;
use App\Model\Account;
use App\Core\Validator;
use App\Core\ValidationRule;
use App\Core\Result;
use App\Core\Router;

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
        $data['account_id'] ??= 0;
        $data['account_type'] ??= 'User'; // User, Admin
        $data['gender'] ??= 'M'; // M, F
        $data['hidden_personality'] ??= 0; // When gender is F account can set this to hide photo, email and phone from gender M
        $data['name'] ??= '';
        $data['surname'] ??= '';
        $data['country_code'] ??= '';
        $data['email'] ??= '';
        $data['email_verification'] ??= '';
        $data['phone'] ??= '';
        $data['phone_verification'] ??= '';
        $data['password'] ??= '';
        $data['personal_photo'] ??= '';
        $data['personal_photo_verified'] ??= 0;
        $data['register_date'] ??= date('Y-m-d H:i:s');
        $data['admin_notes'] ??= '';
        $data['account_status'] ??= 'Pending'; // Pending, Active, Suspended

        $this->search = json_decode($data['search'] ?? '[]', true);
        
        // Only hash password if not empty
        if(!empty($data['password'])){
            $data['password'] = hash('sha256', $data['password']);
        }

        $this->data = $data;

        $this->validator = new Validator($this->data);
        $this->validator->add('account_id', 'Invalid account id', ValidationRule::positive(1));
        $this->validator->add('account_type', 'Invalid account type', ValidationRule::inList(['User', 'Admin']));
        $this->validator->add('gender', 'Invalid gender', ValidationRule::inList(['M', 'F']));
        $this->validator->add('name', 'Name is missing', ValidationRule::string(3, 50));
        $this->validator->add('surname', 'Surname is missing', ValidationRule::string(3, 50));
        $this->validator->add('country_code', 'Invalid country', ValidationRule::string(2, 2));
        $this->validator->add('email', 'Invalid email', ValidationRule::email());
        $this->validator->add('phone', 'Invalid phone', ValidationRule::regexp('#[+0]?[0-9\s]{4,12}#'));
        $this->validator->add('password', 'Password is missing', ValidationRule::notEmpty());
    }

    private function generateEmailVerification($email): string{
        return md5($email . microtime() . date('YmdHis'));
    }

    private function generatePhoneVerification(): string{
        return (string) random_int(10000, 99999);
    }

    // Guard: User
    public function Signup(array $routeParams = []): Result
    {
        $this->data['account_type'] = 'User';
        $this->data['account_status'] = 'Pending';

        return $this->Create($routeParams);
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
            'phone' => $this->data['phone']
        ]);

        // DB Error
        if(is_null($resDuplicate->data)){
            return $resDuplicate;
        }

        // Existing record found
        if($resDuplicate->data > 0){
            return new Result(
                null,
                sprintf(App::loc('Account exists with ID #%s'), $resDuplicate->data),
                'error'
            );
        }

        $resCreate = $this->account->Create([
            'account_type' => $this->data['account_type'],
            'gender' => $this->data['gender'],
            'hidden_personality' => $this->data['hidden_personality'],
            'name' => $this->data['name'],
            'surname' => $this->data['surname'],
            'country_code' => $this->data['country_code'],
            'email' => $this->data['email'],
            'email_verification' => $this->generateEmailVerification($this->data['email']), // embed in a link
            'phone' => $this->data['phone'],
            'phone_verification' => $this->generatePhoneVerification(), // send in SMS
            'password' => $this->data['password'],
            'personal_photo' => $this->data['personal_photo'],
            'personal_photo_verified' => 0, // manual by admin
            'register_date' => date('Y-m-d H:i:s'),
            'admin_notes' => $this->data['admin_notes'],
            'account_status' => $this->data['account_status']
        ]);

        // DB Error
        if(is_null($resCreate->data)){
            return $resCreate;
        }

        // Return created record
        $resRead = $this->account->Read([
            'account_id' => $resCreate->data
        ]);

        // DB Error
        if(is_null($resRead->data)){
            return $resRead;
        }

        return new Result(
            $resRead->data,
            App::loc('Account created'),
            'success',
            '',
            $resRead->metaData
        );
    }

    // Guard: User
    public function Profile(array $routeParams = []): Result
    {
        $routeParams['account_id'] = Auth::getUser('account_id');

        return $this->Read($routeParams);
    }
    
    // Guard: Admin
    public function Read(array $routeParams = []): Result
    {
        $isOneRecord = false;
        $message = '';
        $messageType = '';

        if(array_key_exists('account_id', $routeParams) && $routeParams['account_id'] > 0){
            $this->search = ['account_id' => $routeParams['account_id']];
            $isOneRecord = true;
        };

        $resRead = $this->account->Read($this->search);

        // DB Error
        if(is_null($resRead->data)){
            return $resRead;
        }

        if($isOneRecord && empty($resRead->data)){
            $message = App::loc('Record not found');
            $messageType = 'info';
        }

        return new Result(
            $resRead->data,
            $message,
            $messageType,
            '',
            $resRead->metaData
        );
    }

    // Guard: User
    public function UpdateProfile(array $routeParams = []): Result
    {
        $this->data['account_id'] = Auth::getUser('account_id');
        
        $resAccount = $this->account->Read(['account_id' => $this->data['account_id']]);
        if(empty($resAccount->data)){
            return new Result(
                null,
                App::loc('Profile not found'),
                'error'
            );
        }

        // Don't let normal user to change these properites
        $this->data['account_type'] = $resAccount[0]['account_type'];
        $this->data['gender'] = $resAccount[0]['gender'];
        $this->data['account_status'] = $resAccount[0]['account_status'];
        
        // Check if email, phone or photo have been changed
        $this->data['email_verification'] = $resAccount[0]['email_verification'];
        $this->data['phone_verification'] = $resAccount[0]['phone_verification'];
        $this->data['personal_photo_verified'] = $resAccount[0]['personal_photo_verified'];

        if($resAccount[0]['email'] != $this->data['email']){
            $this->data['email_verification'] = $this->generateEmailVerification($this->data['email']);
        }

        if($resAccount[0]['phone'] != $this->data['phone']){
            $this->data['phone_verification'] = $this->generatePhoneVerification($this->data['email']);
        }

        if($resAccount[0]['personal_photo'] != $this->data['personal_photo']){
            $this->data['personal_photo_verified'] = 0;
        }

        return $this->Update($routeParams);
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
            'phone' => $this->data['phone']
        ]);

        // DB Error
        if(is_null($resDuplicate->data)){
            return $resDuplicate;
        }

        // Existing record found
        if($resDuplicate->data > 0){
            return new Result(
                null,
                sprintf(App::loc('Account exists with ID #%s'), $resDuplicate->data),
                'error'
            );
        }

        $resUpdate = $this->account->Update([
            'account_id' => $this->data['account_id'],
            'gender' => $this->data['gender'],
            'hidden_personality' => $this->data['hidden_personality'],
            'name' => $this->data['name'],
            'surname' => $this->data['surname'],
            'country_code' => $this->data['country_code'],
            'email' => $this->data['email'],
            'email_verification' => $this->data['email_verification'],
            'phone' => $this->data['phone'],
            'phone_verification' => $this->data['phone_verification'],
            'password' => $this->data['password'],
            'personal_photo' => $this->data['personal_photo'],
            'personal_photo_verified' => $this->data['personal_photo_verified'],
            'admin_notes' => $this->data['admin_notes'],
            'account_status' => $this->data['account_status']
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

        // Update current loged in account
        if(Auth::getUser('account_id') == $this->data['account_id']){
            Auth::setUser($resRead->data[0]);
        }

        return new Result(
            $resRead->data,
            App::loc('Account updated'),
            'success',
            '',
            $resRead->metaData
        );
    }

    // Guard: User
    public function DeleteProfile(array $routeParams = []): Result
    {
        $this->data['account_id'] = Auth::getUser('account_id');

        return $this->Delete($routeParams);
    }

    // Guard: Admin
    public function Delete(array $routeParams = []): Result
    {
        $resReferenced = $this->account->isReferenced([
            'account_id' => $this->data['account_id']
        ]);

        if($resReferenced->data){
            return new Result(
                $resReferenced->data,
                App::loc('Account is referenced by'),
                'reference_error',
                ''
            );
        }
        
        if ($this->account->Delete(['account_id' => $this->data['account_id']])){
            return new Result(
                $this->data['account_id'],
                App::loc('Account deleted'),
                'success',
                ''
            );
        }

        return new Result(
            null,
            App::loc('Failed to delete account'),
            'error',
            ''
        );
    }

    // Guard: None
    public function Login(Array $routeParams=[]){
        $this->validator->add('email_phone', 'Invalid email or phone number', function(){
            return ValidationRule::email() || ValidationRule::regexp('#[+0]?[0-9\s]{4,12}#');
        });

        if($dataErr = $this->validator->validateOnly(['email_phone'])){
            return new Result(
                $dataErr,
                App::loc('Some data are missing or invalid'),
                'validation_error'
            );
        }

        $this->validator->remove('email_phone');

        $resLogin = $this->account->Login([
            'email_phone' => $this->data['email_phone'],
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
        if($accountData['account_status'] != 'Active'){
            return new Result(
                $accountData,
                sprintf(App::loc('Your account is %s'), App::loc($accountData['account_status'])),
                'error',
                ''
            );
        }

        Auth::setUser($resLogin->data[0]);

        $redirectUrl = Router::getRedirectViewCode();
        if(empty($redirectUrl)){
            $redirectUrl = '/Dashboard';
        }
        
        return new Result(
            $resLogin->data,
            sprintf(App::loc("Welcome %s"), Auth::getUser('name')),
            'success',
            $redirectUrl
        );
    }

    // Guard: None
    public function Logout(Array $routeParams=[]){
        if(!Auth::isLoggedIn()){
            return new Result(
                null,
                APP::loc("You are not logged in"),
                'error',
                '/Home'
            );
        }

        $name = Auth::getUser('name');
        Auth::destroyUser();

        return new Result(
            null,
            sprintf(APP::loc("Good Bye %s"), $name),
            'success',
            '/Home'
        );
    }
}
