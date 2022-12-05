<?php
declare (strict_types = 1);

namespace App\Model;

use App\Core\App;
use App\Core\Model;
use App\Core\Result;
use Exception;

class Notification extends Model {

    public function Create(array $params = []): Result
    {
        $sql = "INSERT INTO notifications
            (account_id, importance, notification, params, notification_link, notification_status, create_date)
            VALUES(:account_id, :importance, :notification, :params, :notification_link, :notification_status, :create_date)";

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
        $sql = "SELECT notification_id, account_id, importance, notification, notification_link, params, notification_status, create_date
            FROM notifications";

        $args = [
            'limit' => $limit = App::getPageLimit($params['limit']??0),
            'offset' => App::getPageOffset($params['page']??1, $limit)
        ];

        $filter = $this->buildSQLFilter(
            $params,
            array(
                'notification_id' =>    ['AND', 'notification_id = :sf_notification_id'],
                'account_id'=>          ['AND', "account_id =:sf_account_id"],
                'notification_status'=> ['AND', "notification_status =:sf_notification_status"]
            ),
            'sf_'
        );

        $sql .= $filter->Query;
        $sql .= " ORDER BY create_date DESC
            LIMIT :limit OFFSET :offset;";
        
        // Adding meta data when reading all records
        $hasMeta = false;
        if (($params['notification_id']??0) == 0) {
            $hasMeta = true;

            $sql .= "SELECT COUNT(*) AS total_records, :limit AS records_per_page
                FROM notifications";
            $sql .= $filter->Query;
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
                App::loc('Failed to read {object}', '', ['object' => 'notifications']),
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
        $sql = "UPDATE notifications
            SET
                notification_status = :notification_status
            WHERE notification_id = :notification_id AND account_id = :account_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['notification_id']
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
        $sql = "DELETE FROM notifications WHERE notification_id = :notification_id AND account_id = :account_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['notification_id']
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

    // ##### Additional model's queries ##### //

    public function Count(array $params = []): Result
    {
        $sql = "SELECT SUM(account_notifications) AS account_notifications FROM (
                SELECT COUNT(*) AS account_notifications
                FROM notifications
                WHERE account_id = :account_id AND notification_status = 'New'
                UNION ALL
                SELECT 1
                FROM messages
                WHERE recipient_id = :account_id AND message_status = 'New'
                GROUP BY sender_id, conversation_id
            ) AS src;";

        $rowsets = [];
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
                null,
                App::loc('Failed to count {object}', '', ['object' => 'notifications']),
                'error'
            );
        }

        return new Result(
            $rowsets[0]['account_notifications']
        );
    }

    public function Cleanup(array $params = []): Result
    {
        $sql = "DELETE FROM notifications WHERE notification_status = 'Read' AND (TO_DAYS(NOW()) - TO_DAYS(create_date)) >= :day_period;";
    
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