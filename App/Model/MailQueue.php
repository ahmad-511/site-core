<?php
declare (strict_types = 1);

namespace App\Model;

use App\Core\App;
use App\Core\Model;
use App\Core\Result;

use Exception;

class MailQueue extends Model {

    public function Create(array $params = []): Result
    {
        $sql = "INSERT INTO mail_queue
            (`to`, `subject`, body, params, use_template, priority, language_code, queue_date, status, remarks)
            VALUES(:to, :subject, :body, :params, :use_template, :priority, :language_code, :queue_date, :status, :remarks);";

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
        $sql = "SELECT queue_id, `to`, `subject`, body, params, use_template, priority, language_code, queue_date, status, remarks
            FROM mail_queue
            WHERE status = 'Pending' AND (:priority = -1 OR priority = :priority)
            ORDER BY queue_date, priority
            LIMIT :limit;";

        $rowsets = [];
        try {
            $rowsets = $this->query($sql, [
                'priority' => intval($params['priority']??-1),
                'limit' => intval($params['batch_size'])
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
                App::loc('Failed to read {object}', '', ['object' => 'mail queue']),
                'error'
            );
        }

        return new Result(
            $rowsets
        );
    }
    
    public function Update(array $params = []): Result
    {
        $sql = "UPDATE mail_queue
            SET
                status = :status,
                remarks = :remarks
            WHERE queue_id = :queue_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['queue_id']
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
        $sql = "DELETE FROM mail_queue WHERE queue_id = :queue_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['queue_id']
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