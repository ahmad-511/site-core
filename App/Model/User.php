<?php
declare (strict_types = 1);

namespace App\Model;

use App\Core\App;
use App\Core\Model;
use App\Core\Result;
use Exception;

class User extends Model {

    public function isDuplicated(array $params = []):Result{
        $sql = "SELECT user_id
            FROM users";

        $criteria = [
            'email, phone'=> "(email = :sf_email OR phone = :sf_phone)"
        ];
        
        if($params['user_id']??0){
            $criteria['user_id'] = ['AND', 'user_id != :sf_user_id'];
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
                empty($rowsets)?0: $rowsets[0]['user_id']
            );

        }catch (Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }
    }

    public function isReferenced():Result{
        // No refrences for users yet
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
        $sql = "INSERT INTO users
            (user_type, gender, hidden_personality, name, surname, country_code, email, email_verification, phone, phone_verification, password, personal_photo, personal_photo_verification, register_date, admin_notes, user_status)
            VALUES(:user_type, :gender, :hidden_personality, :name, :surname, :country_code, :email, :email_verification, :phone, :phone_verification, :password, :personal_photo, :personal_photo_verification, :register_date, :admin_notes, :user_status)";

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
        $sql = "SELECT user_id, user_type, gender, hidden_personality, name, surname, country_code, email, email_verification, phone, phone_verification, password, personal_photo, personal_photo_verification, register_date, admin_notes, user_status
            FROM users";

        $args = [
            'limit' => RECORDS_PER_PAGE,
            'offset' => App::getPageOffset($params['page']??1)
        ];

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'user_id' =>             ['AND', 'user_id = :sf_user_id', [0, '0']],
                'user_type'=>            ['AND', "user_type = :sf_user_type"],
                'gender'=>                  ['AND', "gender = :sf_gender"],
                'country_code'=>            ['AND', "country_code = :sf_country_code"],
                'email'=>                   ['AND', "email = :sf_email"],
                'phone'=>                   ['AND', "phone = :sf_phone"],
                'register_date_from'=>      ['AND', "register_date >= :sf_register_date_from"],
                'register_date_to'=>        ['AND', "register_date <= :sf_register_date_to"],
                'admin_notes'=>             ['AND', "admin_notes LIKE CONCAT('%', :sf_admin_notes, '%')"],
                'user_status'=>          ['AND', "user_status = :sf_user_status"]
            ),
            'sf_'
        );

        $sql .= $filter->Query;

        $sql .= " ORDER BY register_date DESC
            LIMIT :limit OFFSET :offset;";
        
        // Adding meta data when reading all records
        $hasMeta = false;
        if (($params['user_id']??0) == 0) {
            $hasMeta = true;

            $sql .= "SELECT COUNT(*) AS total_records, :limit AS records_per_page
                FROM users";
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
                'Failed to read users',
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
        $sql = "UPDATE users
            SET
                hidden_personality = :hidden_personality,
                name = :name,
                surname = :surname,
                country_code = :country_code,
                email = :email,
                phone = :phone,
                password = IF(:password = '', password, :password),
                personal_photo = :personal_photo
            WHERE user_id = :user_id;";

        // Don't update empty password
        if($params['password'] == hash('sha256', '')){
            $params['password'] = '';
        }

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['user_id']
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
        $sql = "DELETE FROM users WHERE user_id = :user_id";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['user_id']
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
        $sql = "SELECT user_id, user_type, name, user_status
            FROM users
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