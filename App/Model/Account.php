<?php
declare (strict_types = 1);

namespace App\Model;

use App\Core\App;
use App\Core\Model;
use App\Core\Result;

use Exception;

class Account extends Model {

    public function isDuplicated(array $params = []):Result{
        $sql = "SELECT account_id
            FROM accounts";

        $criteria = [
            'email, mobile'=> "(email = :sf_email OR mobile = :sf_mobile)"
        ];
        
        if(($params['account_id']??0) > 0){
            $criteria['account_id'] = ['AND', 'account_id != :sf_account_id'];
        }

        $filter = $this->buildSQLFilter(
            $params,
            $criteria,
            'sf_'
        );

        $sql .= $filter->Query;

        try {
            $rowsets = $this->query($sql, $filter->Params);
            return new Result(
                empty($rowsets)?0: $rowsets[0]['account_id']
            );

        }catch (Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function isReferenced(array $params = []):Result{
        // No refrences for accounts yet
        try{
            return new Result(
                []
            );

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function Create(array $params = []): Result
    {
        $sql = "INSERT INTO accounts
            (role, gender, first_name, last_name, email, email_verification, mobile, password, password_reset_code, preferred_language, register_date, admin_notes, account_status)
            VALUES(:role, :gender, :first_name, :last_name, :email, :email_verification, :mobile, :password, :password_reset_code, :preferred_language, :register_date, :admin_notes, :account_status)";

        try {
            $id = $this->query($sql, $params);
            return new Result(
                $id
            );

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function Read(array $params = []): Result
    {
        $sql = "SELECT a.account_id, a.role, a.gender, a.first_name, a.last_name, a.email, a.email_verification, a.mobile, '' AS password, a.preferred_language, IFNULL(att.path, 'x') AS personal_photo, a.register_date, a.admin_notes, a.account_status
            FROM accounts AS a
            LEFT JOIN attachments AS att ON a.account_id = att.account_id AND att.type = 'PersonalPhoto'";

        $args = [
            'limit' => $limit = App::getPageLimit($params['limit']??0),
            'offset' => App::getPageOffset($params['page']??1, $limit)
        ];

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'account_id' =>             ['AND', 'account_id = :sf_account_id', [0, '0']],
                'role'=>                    ['AND', "role = :sf_role"],
                'gender'=>                  ['AND', "gender = :sf_gender"],
                'first_name'=>              ['AND', "first_name LIKE CONCAT('%', :sf_first_name, '%')"],
                'last_name'=>               ['AND', "last_name LIKE CONCAT('%', :sf_last_name, '%')"],
                'mobile'=>                  ['AND', "mobile = :sf_mobile"],
                'email'=>                   ['AND', "email = :sf_email"],
                'password_reset_code'=>     ['AND', "a.password_reset_code = :sf_password_reset_code"],
                'register_date_from'=>      ['AND', "register_date >= :sf_register_date_from"],
                'register_date_to'=>        ['AND', "register_date <= DATE_ADD(:sf_register_date_to, INTERVAL 1 DAY)"],
                'admin_notes'=>             ['AND', "admin_notes LIKE CONCAT('%', :sf_admin_notes, '%')"],
                'account_status'=>          ['AND', "account_status = :sf_account_status"],
                'ids'=>                     ['AND', "account_id IN(:sf_ids)", [], true]
            ),
            'sf_'
        );

        $sql .= $filter->Query;

        $sql .= " ORDER BY register_date DESC
            LIMIT :limit OFFSET :offset;";
        
        // Adding meta data when reading all records
        $hasMeta = false;
        if (($params['account_id']??0) == 0) {
            $hasMeta = true;

            $sql .= "SELECT COUNT(*) AS total_records, :limit AS records_per_page
                FROM accounts";
            $sql .= $filter->Query.";";
        }

        $args = array_merge($args, $filter->Params);
        
        $rowsets = [];
        try {
            $rowsets = $this->query($sql, $args);
        }catch (Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }

        if ($rowsets === false) {
            return new Result(
                [],
                App::loc('Failed to read {object}', '', ['object' => 'accounts']),
                'error'
            );
        }

        return new Result(
            $hasMeta?$rowsets[0]:$rowsets,
            '',
            '',
            '',
            $hasMeta?$rowsets[1][0]:null
        );
    }
    
    public function List(array $params = []): Result
    {
        $sql = "SELECT a.account_id, a.account_type, a.gender, CONCAT(a.first_name, ' ', a.last_name) AS account, a.mobile, IFNULL(att.path, '') AS personal_photo, a.preferred_language
            FROM accounts AS a
            LEFT JOIN attachments AS att ON a.account_id = att.account_id AND att.type = 'PersonalPhoto'";

        $args = [
            'limit' => $limit = App::getPageLimit($params['limit']??0),
            'offset' => App::getPageOffset($params['page']??1, $limit)
        ];

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'account_type'=>    ['AND', "account_type = :sf_account_type"],
                'name'=>    ['AND', "CONCAT(a.first_name, ' ' , a.last_name) LIKE CONCAT(:sf_name, '%')"]
            ),
            'sf_'
        );

        $sql .= $filter->Query;

        $sql .= " ORDER BY a.first_name, a.last_name
            LIMIT :limit OFFSET :offset;";

        $args = array_merge($args, $filter->Params);
        
        $rowsets = [];
        try {
            $rowsets = $this->query($sql, $args);
        }catch (Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }

        if ($rowsets === false) {
            return new Result(
                [],
                App::loc('Failed to read {object}', '', ['object' => 'accounts']),
                'error'
            );
        }

        return new Result($rowsets);
    }

    public function Update(array $params = []): Result
    {
        $sql = "UPDATE accounts
            SET
                role = :role,
                gender = :gender,
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                email_verification = :email_verification,
                mobile = :mobile,
                password = IF(:password = '', password, :password),
                preferred_language = :preferred_language,
                admin_notes = :admin_notes,
                account_status = :account_status
            WHERE account_id = :account_id;";

        // Don't update empty password
        if($params['password'] == hash('sha256', '')){
            $params['password'] = '';
        }

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['account_id']
                );
            }

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }
    
    public function SetPasswordResetCode(array $params = []): Result
    {
        $sql = "UPDATE accounts
            SET
                password_reset_code = :password_reset_code
            WHERE account_id = :account_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['account_id']
                );
            }

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function SetNewPassword(array $params = []): Result
    {
        $sql = "UPDATE accounts
            SET
                password = :password,
                password_reset_code = ''
            WHERE account_id = :account_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['account_id']
                );
            }

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function UpdateEmailVerification(array $params = []): Result
    {
        $sql = "UPDATE accounts
            SET
                email_verification = :email_verification,
                account_status = :account_status
            WHERE account_id = :account_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['account_id']
                );
            }

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function Delete(array $params = []): Result
    {
        $sql = "-- Delete notifications
            DELETE
            FROM notifications WHERE account_id = :account_id;
            -- Delete account
            DELETE FROM accounts
            WHERE account_id = :account_id;";
        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['account_id']
                );
            }

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function Login(array $params = []):Result{
        $sql = "SELECT account_id, a.role, a.gender, a.first_name, a.country_code, a.last_name, a.email, a.mobile, IFNULL(att.path, 'x') AS personal_photo, a.account_status, a.lookup_token, a.validator_token,
            FROM accounts AS a
            LEFT JOIN attachments AS att ON a.account_id = att.account_id and att.type = 'PersonalPhoto'
            WHERE (email = :email_mobile OR CONCAT(REPLACE(c.dialing_code, '+', ''), mobile) = :email_mobile) AND password = :password
            LIMIT 1;";

        try{
            $rowsets = $this->query($sql, $params);
            return new Result(
                $rowsets
            );

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function CookieLogin(array $params = []):Result{
        $sql = "SELECT account_id, a.role, a.gender, a.first_name, a.country_code, a.last_name, a.email, a.mobile, IFNULL(att.path, 'x') AS personal_photo, a.account_status, a.lookup_token, a.validator_token
            FROM accounts AS a
            LEFT JOIN attachments AS att ON a.account_id = att.account_id and att.type = 'PersonalPhoto'
            WHERE lookup_token = :lookup_token
            LIMIT 1;";

        try{
            $rowsets = $this->query($sql, $params);
            return new Result(
                $rowsets
            );

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    // Internal use
    public function UpdateTokens(array $params = []): Result
    {
        $sql = "UPDATE accounts
            SET
                lookup_token = :lookup_token,
                validator_token = :validator_token
            WHERE account_id = :account_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    true
                );
            }

        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    // Internal use
    public function Unsubscribe(array $params = []): Result
    {
        $sql = "";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    true
                );
            }
        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }
}