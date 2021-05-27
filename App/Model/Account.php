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
            'email, phone'=> "(email = :sf_email OR phone = :sf_phone)"
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
            (account_type, gender, hidden_personality, name, surname, country_code, email, email_verification, phone, phone_verification, password, personal_photo, personal_photo_verified, register_date, admin_notes, account_status)
            VALUES(:account_type, :gender, :hidden_personality, :name, :surname, :country_code, :email, :email_verification, :phone, :phone_verification, :password, :personal_photo, :personal_photo_verified, :register_date, :admin_notes, :account_status)";

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
        $sql = "SELECT a.account_id, a.account_type, a.gender, a.hidden_personality, a.name, a.surname, a.country_code, IFNULL(c.country, '') AS country, a.email, a.email_verification, a.phone, a.phone_verification, a.password, a.personal_photo, a.personal_photo_verified, a.register_date, a.admin_notes, a.account_status
            FROM accounts AS a
            LEFT JOIN countries AS c ON c.country_code = a.country_code";

        $args = [
            'limit' => RECORDS_PER_PAGE,
            'offset' => App::getPageOffset($params['page']??1)
        ];

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'account_id' =>             ['AND', 'a.account_id = :sf_account_id', [0, '0']],
                'account_type'=>            ['AND', "a.account_type = :sf_account_type"],
                'gender'=>                  ['AND', "a.gender = :sf_gender"],
                'country_code'=>            ['AND', "a.country_code = :sf_country_code"],
                'email'=>                   ['AND', "a.email = :sf_email"],
                'phone'=>                   ['AND', "a.phone = :sf_phone"],
                'personal_photo_verified'=> ['AND', "a.personal_photo_verified = :sf_personal_photo_verified"],
                'register_date_from'=>      ['AND', "a.register_date >= :sf_register_date_from"],
                'register_date_to'=>        ['AND', "a.register_date <= :sf_register_date_to"],
                'admin_notes'=>             ['AND', "a.admin_notes LIKE CONCAT('%', :sf_admin_notes, '%')"],
                'account_status'=>          ['AND', "a.account_status = :sf_account_status"]
            ),
            'sf_'
        );

        $sql .= $filter->Query;

        $sql .= " ORDER BY a.register_date DESC
            LIMIT :limit OFFSET :offset;";
        
        // Adding meta data when reading all records
        $hasMeta = false;
        if (($params['account_id']??0) == 0) {
            $hasMeta = true;

            $sql .= "SELECT COUNT(*) AS total_records, :limit AS records_per_page
                FROM accounts AS a
                LEFT JOIN countries AS c ON c.country_code = a.country_code";
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
                'Failed to read accounts',
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

    public function Update(array $params = []): Result
    {
        $sql = "UPDATE accounts
            SET
                hidden_personality = :hidden_personality,
                name = :name,
                surname = :surname,
                country_code = :country_code,
                email = :email,
                phone = :phone,
                password = IF(:password = '', password, :password),
                personal_photo = :personal_photo
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

    public function Delete(array $params = []): Result
    {
        $sql = "DELETE FROM accounts WHERE account_id = :account_id";

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
        $sql = "SELECT account_id, account_type, name, surname, account_status
            FROM accounts
            WHERE (email = :email_phone OR phone = :email_phone) AND password = :password
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
}