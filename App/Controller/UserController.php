<?php
declare (strict_types = 1);
namespace App\Controller;

use App\Core\Controller;
use App\Core\App;
use App\Core\Auth;
use App\Model\User;
use App\Core\Validator;
use App\Core\ValidationRule;
use App\Core\Result;
use App\Core\Router;

class UserController extends Controller
{
    private User $user;
    private array $data = [];
    private array $search;
    private Validator $validator;

    public function __construct(array $data = [])
    {
        $this->account = new User();

        // Setup defaults
        $data['account_id'] ??= 0;
        $data['account_type'] ??= 'User'; // User, Admin
        $data['gender'] ??= 'M'; // M, F
        $data['hidden_personality'] ??= 0; // When gender is F user can set this to hide photo, email and phone from gender M
        $data['name'] ??= '';
        $data['surname'] ??= '';
        $data['country_code'] ??= '';
        $data['email'] ??= '';
        $data['email_verification'] ??= '';
        $data['phone'] ??= '';
        $data['phone_verification'] ??= '';
        $data['password'] ??= '';
        $data['personal_photo'] ??= '';
        $data['personal_photo_verification'] ??= '';
        $data['register_date'] = date('Y-m-d H:i:s');
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

    public function Create(array $routeParams = []): Result
    {
        if($dataErr = $this->validator->validate(['account_id'])){
            return new Result(
                $dataErr,
                'Some data are missing or invalid',
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
                "Account exists with ID #{$resDuplicate->data}",
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
            'email_verification' => md5($this->data['email'] . microtime()), // embed in a link
            'phone' => $this->data['phone'],
            'phone_verification' => random_int(10000, 99999), // send in SMS
            'password' => $this->data['password'],
            'personal_photo' => $this->data['personal_photo'],
            'personal_photo_verification' => 'No', // manual by admin
            'register_date' => $this->data['register_date'],
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
            'Account created',
            'success',
            '',
            $resRead->metaData
        );
    }

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

    public function Update(array $routeParams = []): Result
    {
        if($dataErr = $this->validator->validate(['account_id', 'account_type', 'gender', 'hidden_personality', 'name', 'surname'])){
            return new Result(
                $dataErr,
                'Some data are missing or invalid',
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
                "Account exists with ID #{$resDuplicate->data}",
                'error'
            );
        }

        $resUpdate = $this->account->Update([
            'account_id' => $this->data['account_id'],
            'hidden_personality' => $this->data['hidden_personality'],
            'name' => $this->data['name'],
            'surname' => $this->data['surname'],
            'country_code' => $this->data['country_code'],
            'email' => $this->data['email'],
            'phone' => $this->data['phone'],
            'password' => $this->data['password'],
            'personal_photo' => $this->data['personal_photo'],
            'personal_photo_verification' => 'No' // manual by admin
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
            'Account updated',
            'success',
            '',
            $resRead->metaData
        );
    }

    public function Delete(array $routeParams = []): Result
    {
        if($resReferenced = $this->account->isReferenced()){
            return new Result(
                $resReferenced->data,
                'Account is referenced by',
                'reference_error',
                ''
            );
        }
        
        if ($this->account->Delete(['account_id' => $this->data['account_id']])){
            return new Result(
                $this->data['account_id'],
                'Account deleted',
                'success',
                ''
            );
        }

        return new Result(
            null,
            'Failed to delete user',
            'error',
            ''
        );
    }

    public function Login(Array $routeParams=[]){
        $this->validator->add('email_phone', 'Invalid email or phone number', function(){
            return ValidationRule::email() || ValidationRule::regexp('#[+0]?[0-9\s]{4,12}#');
        });

        if($dataErr = $this->validator->validateOnly(['email_phone'])){
            return new Result(
                $dataErr,
                'Some data are missing or invalid',
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
                'Login failed',
                'error',
                ''
            );
        }

        $userData = $resLogin->data[0];

        // Check account status
        if($userData['account_status'] != 'Active'){
            return new Result(
                $userData,
                sprintf(App::loc('Your account is %s'), App::loc($userData['account_status'])),
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
