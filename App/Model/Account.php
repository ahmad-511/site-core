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
            (account_type, gender, hidden_personality, name, surname, country_code, email, email_verification, mobile, mobile_verification, password, preferred_language, notification_emails, personal_photo_verification, register_date, admin_notes, account_status, remarks)
            VALUES(:account_type, :gender, :hidden_personality, :name, :surname, :country_code, :email, :email_verification, :mobile, :mobile_verification, :password, :preferred_language, :notification_emails, :personal_photo_verification, :register_date, :admin_notes, :account_status, :remarks)";

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
        $sql = "SELECT a.account_id, a.account_type, a.gender, a.hidden_personality, a.name, a.surname, a.country_code, IFNULL(c.country, '') AS country, IF(c.country_alt = '', c.country, c.country_alt) AS country_alt, c.mobile_number_validator, a.email, a.email_verification, c.dialing_code, a.mobile, a.mobile_verification, '' AS password, a.preferred_language, a.notification_emails, IFNULL(att.path, 'x') AS personal_photo, a.personal_photo_verification, a.register_date, a.admin_notes, a.account_status, a.remarks, a.rating, a.ratings_count
            FROM accounts AS a
            LEFT JOIN countries AS c ON c.country_code = a.country_code
            LEFT JOIN attachments AS att ON a.account_id = att.account_id AND att.type = 'PersonalPhoto'";

        $args = [
            'limit' => $limit = App::getPageLimit($params['limit']??0),
            'offset' => App::getPageOffset($params['page']??1, $limit)
        ];

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'account_id' =>             ['AND', 'a.account_id = :sf_account_id', [0, '0']],
                'account_type'=>            ['AND', "a.account_type = :sf_account_type"],
                'gender'=>                  ['AND', "a.gender = :sf_gender"],
                'name'=>                    ['AND', "a.name LIKE CONCAT('%', :sf_name, '%')"],
                'surname'=>                 ['AND', "a.surname LIKE CONCAT('%', :sf_surname, '%')"],
                'country_code'=>            ['AND', "a.country_code = :sf_country_code"],
                'mobile'=>                  ['AND', "a.mobile = :sf_mobile"],
                'country_mobile'=>          ['AND', "CONCAT(c.dialing_code, a.mobile) = :sf_country_mobile"],
                'email'=>                   ['AND', "a.email = :sf_email"],
                'personal_photo_verification'=>['AND', "(a.personal_photo_verification = :sf_personal_photo_verification OR :sf_personal_photo_verification = 'x')", ['']],
                'register_date_from'=>      ['AND', "a.register_date >= :sf_register_date_from"],
                'register_date_to'=>        ['AND', "a.register_date <= DATE_ADD(:sf_register_date_to, INTERVAL 1 DAY)"],
                'remarks'=>                 ['AND', "a.remarks LIKE CONCAT('%', :sf_remarks, '%')"],
                'admin_notes'=>             ['AND', "a.admin_notes LIKE CONCAT('%', :sf_admin_notes, '%')"],
                'account_status'=>          ['AND', "a.account_status = :sf_account_status"],
                'ids'=>                     ['AND', "a.account_id IN(:sf_ids)", [], true]
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
        $sql = "SELECT a.account_id, a.account_type, a.gender, CONCAT(a.name, ' ', a.surname) AS account, a.country_code, IFNULL(c.country, '') AS country, IF(c.country_alt = '', c.country, c.country_alt) AS country_alt, a.mobile, IFNULL(att.path, '') AS personal_photo, a.preferred_language, a.notification_emails
            FROM accounts AS a
            LEFT JOIN countries AS c ON c.country_code = a.country_code
            LEFT JOIN attachments AS att ON a.account_id = att.account_id AND att.type = 'PersonalPhoto'";

        $args = [
            'limit' => $limit = App::getPageLimit($params['limit']??0),
            'offset' => App::getPageOffset($params['page']??1, $limit)
        ];

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'account_type'=>    ['AND', "account_type = :sf_account_type"],
                'name'=>    ['AND', "CONCAT(a.name, ' ' , a.surname) LIKE CONCAT(:sf_name, '%')"]
            ),
            'sf_'
        );

        $sql .= $filter->Query;

        $sql .= " ORDER BY a.name, a.surname
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
                account_type = :account_type,
                gender = :gender,
                hidden_personality = :hidden_personality,
                name = :name,
                surname = :surname,
                country_code = :country_code,
                email = :email,
                email_verification = :email_verification,
                mobile = :mobile,
                mobile_verification = :mobile_verification,
                password = IF(:password = '', password, :password),
                preferred_language = :preferred_language,
                notification_emails = :notification_emails,
                personal_photo_verification = :personal_photo_verification,
                admin_notes = :admin_notes,
                remarks = :remarks,
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
    public function UpdateMobileVerification(array $params = []): Result
    {
        $sql = "UPDATE accounts
            SET
                mobile_verification = :mobile_verification,
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
        $sql = "-- Delete all not accepted ride requests
            DELETE
            FROM ride_requests
            WHERE account_id = :account_id AND request_status != 'Accepted';
            
            -- Delete canceled rides, related request will be caceled too and got deleted in the previous query
            DELETE
            FROM rides
            WHERE account_id = :account_id AND ride_status = 'Canceled';
            
            -- Delete rides that have no accepted requests
            DELETE
            FROM rides
            WHERE ride_id IN (
                SELECT ar.ride_id
                FROM rides AS ar
                WHERE ar.account_id = :account_id
                EXCEPT
                SELECT DISTINCT r.ride_id
                FROM rides AS r
                INNER JOIN ride_requests AS rr ON rr.ride_id = r.ride_id
                WHERE r.account_id = :account_id AND rr.request_status = 'Accepted'
            );
            
            -- Delete cars that are not used in rides or in rides got deleted in previous query
            DELETE c
            FROM cars AS c
            LEFT JOIN rides AS r ON r.car_id = c.car_id
            WHERE c.account_id = :account_id AND r.ride_id IS NULL;
            
            -- Delete notifications
            DELETE
            FROM notifications WHERE account_id = :account_id;
            
            -- Delete ratings if both accounts are deleted
            DELETE r
            FROM ratings AS r
            LEFT JOIN accounts AS a ON a.account_id = r.account_id
            LEFT JOIN accounts AS ba ON ba.account_id = r.by_account_id
            WHERE :account_id IN(r.account_id, r.by_account_id) AND a.account_status = 'Deleted' AND ba.account_status = 'Deleted';
            
            -- Delete reports if both accounts are deleted
            DELETE r
            FROM reports AS r
            LEFT JOIN accounts AS a ON a.account_id = r.account_id
            LEFT JOIN accounts AS ba ON ba.account_id = r.by_account_id
            WHERE :account_id IN(r.account_id, r.by_account_id) AND a.account_status = 'Deleted' AND ba.account_status = 'Deleted';
            
            -- conversations will be cleaned up periodically
            
            -- Update account name, surname, status and admin notes
            UPDATE accounts
            SET admin_notes = CONCAT(admin_notes, '\n', 'name: ', `name`, ' ', surname),
                `name` = 'Deleted account',
                surname = '',
                account_status = 'Deleted'
            WHERE account_id = :account_id AND account_status != 'Deleted';";
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
    
    public function WarnPending(array $params = []): Result
    {
        $sql = "SELECT account_id, name, surname, mobile, email_verification, email, preferred_language, register_date
            FROM accounts
            WHERE account_status = 'Pending' AND DATEDIFF(NOW(), register_date) > :days_period;
            UPDATE accounts
            SET account_status = 'Warned'
            WHERE account_status = 'Pending' AND DATEDIFF(NOW(), register_date) > :days_period;";

        $rowsets = [];
        try {
            $rowsets = $this->query($sql, $params);
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

    public function DeleteWarned(array $params = []): Result
    {
        $sql = "SELECT account_id, name, surname, email, email_verification, mobile, preferred_language, register_date
            FROM accounts
            WHERE account_status = 'Warned' AND DATEDIFF(NOW(), register_date) > :days_period;
            DELETE FROM accounts
            WHERE account_status = 'Warned' AND DATEDIFF(NOW(), register_date) > :days_period;";

        $rowsets = [];
        try {
            $rowsets = $this->query($sql, $params);
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

    public function Login(array $params = []):Result{
        $sql = "SELECT a.account_id, a.account_type, a.gender, a.hidden_personality, a.name, a.country_code, a.surname, a.email, a.mobile, IFNULL(att.path, 'x') AS personal_photo, a.account_status
            FROM accounts AS a
            INNER JOIN countries AS c ON c.country_code = a.country_code
            LEFT JOIN attachments AS att ON a.account_id = att.account_id and att.type = 'PersonalPhoto'
            WHERE (a.email = :email_mobile OR CONCAT(REPLACE(c.dialing_code, '+', ''), a.mobile) = :email_mobile) AND password = :password
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

    public function UpdatePersonalPhoto(array $params = []): Result
    {
        $sql = "UPDATE accounts
            SET
                personal_photo_verification = ''
            WHERE account_id = :account_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['personal_photo']
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
    public function CountryValidationRules(array $params = []): Result
    {
        $sql = "SELECT mobile_number_validator, plate_number_validator
            FROM countries
            WHERE country_code = (SELECT country_code FROM accounts WHERE account_id = :account_id) LIMIT 1;";
        
        try {
            $rowsets = $this->query($sql, [
                'account_id' => $params['account_id']
                ]);
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
                App::loc('Failed to get country validation rules'),
                'error'
            );
        }

        return new Result($rowsets);
    }

    // Internal use
    public function UpdateRating(array $params = []): Result
    {
        $sql = "UPDATE accounts
            SET
                rating = IFNULL((SELECT ROUND(AVG(rating), 1) FROM ratings WHERE account_id = :account_id AND rating_status = 'Published'), 0),
                ratings_count = IFNULL((SELECT COUNT(*) FROM ratings WHERE account_id = :account_id AND rating_status = 'Published'), 0)
            WHERE account_id = :account_id;
            SELECT rating, ratings_count FROM accounts WHERE account_id = :account_id;";

        try {
            $rowsets = $this->query($sql, $params);
        }catch(Exception $ex){
            return new Result(
                null,
                $ex->getMessage(),
                'db_error'
            );
        }

        if ($rowsets === false) {
            return new Result(
                [],
                App::loc('Failed to get account rating'),
                'error'
            );
        }

        return new Result($rowsets);
    }

    // Internal use
    public function Unsubscribe(array $params = []): Result
    {
        $sql = "UPDATE accounts
            SET
                notification_emails = :notification_emails
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
}