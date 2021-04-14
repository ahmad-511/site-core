<?php
declare (strict_types = 1);
namespace App\Model;

use App\Core\App;
use App\Core\Auth;
use App\Core\Model;
use App\Core\Validator;
use App\Core\Result;
use App\Core\Router;

class User extends Model
{
    private $user_id;
    private $email;
    private $password;
    private $display_name;
    private $search;
    private $validator;

    public function __construct(array $props = [])
    {
        $this->user_id = $props['user_id'] ?? 0;
        $this->email = $props['email'] ?? '';
        $this->password = $props['password'] ?? '';
        $this->display_name = $props['display_name'] ?? '';
        $this->search = json_decode($props['search'] ?? '[]', true);
        
        // Only hash password if not empty
        if(!empty($this->password)){
            $this->password = hash('sha256', $this->password);
        }

        $this->validator = new Validator($this);
        $this->validator->add('user_id', 'Invalid user id', Validator::positive(1));
        $this->validator->add('email', 'Invalid email', Validator::email());
        $this->validator->add('password', 'Password is missing', Validator::notEmpty());
        $this->validator->add('display_name', 'Name is missing', Validator::notEmpty());
    }

    private function isDuplicated($isEdit = false):int{
        $sql = "SELECT user_id
            FROM users";

        $criteria = [
            'email'=> ['WHERE', 'AND', "email = :sf_email"]
        ];

        if($isEdit){
            $criteria['user_id'] = ['WHERE', 'AND', 'user_id != :sf_user_id'];
        }

        $filter = $this->buildSQLFilter(
            get_object_vars($this),
            $criteria,
            'sf_'
        );

        $sql .= $filter['sql'];

        $rowsets = $this->query($sql, $filter['params']);

        if (!empty($rowsets)) {
            return intval($rowsets[0]['user_id']);
        }

        return 0;
    }

    private function isReferenced():array{
        // No refrences for users yet
        return [];
    }

    public function Create(array $params = []): Result
    {
        if($dataErr = $this->validator->validate(['user_id'])){
            return new Result(
                $dataErr,
                'Some data are missing or invalid',
                'validation_error',
                ''
            );
        }

        if($dId = $this->isDuplicated()){
            return new Result(
                null,
                "User exists with ID #$dId",
                'error',
                ''
            );
        }

        $sql = "INSERT INTO users(email, password, display_name) VALUES(:email, :password, :display_name)";

        if ($this->user_id = $this->query($sql, [
            'email' => $this->email,
            'password' => $this->password,
            'display_name' => $this->display_name
        ])
        ) {
            // Return created record
            $res = $this->Read([$this->user_id]);

            return new Result(
                $res->data,
                'User created',
                'success',
                '',
                $res->metaData
            );
        }

        return new Result(
            null,
            'Failed to create user',
            'error',
            ''
        );
    }

    public function Read(array $params = []): Result
    {
        $sql = "SELECT user_id, email, display_name
            FROM users";

        $args = [
            'limit' => RECORDS_PER_PAGE,
            'offset' => App::getPageOffset($params['page']??1)
        ];

        $withMeta = true;
        
        if (isset($params[0])) {
            $this->search['user_id'] = (int) $params[0];
            $withMeta = false;
        }

        $filter = $this->buildSQLFilter(
            $this->search,
            array(
                'user_id' =>    ['WHERE', 'AND', 'user_id = :sf_user_id', [0, '0']],
                'email'=>       ['WHERE', 'AND', "email = :sf_email"],
                'display_name'=>['WHERE', 'AND', "display_name LIKE CONCAT('%', :sf_display_name, '%')"]
            ),
            'sf_'
        );

        $sql .= $filter->Query;

        $sql .= " ORDER BY display_name
            LIMIT :limit OFFSET :offset;";
        
        if($withMeta){
            $sql .= "SELECT COUNT(*) AS total_records, :limit AS records_per_page
                FROM users";
            $sql .= $filter->Query.";";
        }

        $args = array_merge($args, $filter->Params);
        
        $rowsets = $this->query($sql, $args);

        if ($rowsets === false) {
            return new Result(
                [],
                'Failed to read users',
                'error',
                ''
            );
        }

        return new Result(
            $withMeta?$rowsets[0]:$rowsets,
            '',
            '',
            '',
            $withMeta?$rowsets[1][0]:null
        );
    }

    public function Edit(array $params = []): Result
    {
        if($dataErr = $this->validator->validate(['password'])){
            return new Result(
                $dataErr,
                'Some data are missing or invalid',
                'validation_error',
                ''
            );
        }

        if($dId = $this->isDuplicated(true)){
            return new Result(
                null,
                "User exists with ID #$dId",
                'error',
                ''
            );
        }

        $sql = "UPDATE users
            SET
                email = :email,
                password = IF(:password = '', password, :password),
                display_name = :display_name
            WHERE user_id = :user_id;";

        // Don't update empty password
        if($this->password == hash('sha256', '')){
            $this->password = '';
        }

        if ($this->query($sql, [
            'user_id' => $this->user_id,
            'email' => $this->email,
            'password' => $this->password,
            'display_name' => $this->display_name
        ])
        ) {
            // Return created record
            $res = $this->Read([$this->user_id]);

            if(Auth::getUser('user_id') == $this->user_id){
                Auth::setUser($res->data[0]);
            }

            return new Result(
                $res->data,
                'User edited',
                'success',
                '',
                $res->metaData
            );
        }

        return new Result(
            null,
            'Failed to edit user',
            'error',
            ''
        );
    }

    public function Delete(array $params = []): Result
    {
        if($refs = $this->isReferenced()){
            return new Result(
                $refs,
                'User is referenced by',
                'reference_error',
                ''
            );
        }
        
        $sql = "DELETE FROM users WHERE user_id = :user_id";

        if ($this->query($sql, [
            'user_id' => $this->user_id,
        ])
        ) {
            return new Result(
                null,
                'User deleted',
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

    public function Login(Array $params=[]){
        $sql = "SELECT user_id, email, display_name
            FROM users
            WHERE email = :email AND password = :password
            LIMIT 1;";

        $args = [
            'email' => $this->email,
            'password' => $this->password
        ];

        $rowsets = $this->query($sql, $args);

        if ($rowsets === false || empty($rowsets)) {
            return new Result(
                [],
                'Login failed',
                'error',
                ''
            );
        }

        Auth::setUser($rowsets[0]);

        $redirectUrl = Router::getRedirectViewCode();
        if(empty($redirectUrl)){
            $redirectUrl = '/Dashboard';
        }
        
        return new Result(
            $rowsets,
            "Welcome {$rowsets[0]['display_name']}",
            'success',
            $redirectUrl
        );
    }

    public function Logout(Array $params=[]){
        $userName = Auth::getUserName();

        session_destroy();

        return new Result(
            null,
            sprintf(APP::loc("Good Bye %s"), $userName),
            'success',
            '/Home'
        );
    }
}
