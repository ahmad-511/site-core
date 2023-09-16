<?php
declare (strict_types = 1);

namespace App\Model;

use App\Core\Localizer as L;
use App\Core\Model;
use App\Core\Result;

use Exception;

class MailProvider extends Model {

    private function ResetCounters(array $params = []): Result
    {
        $sql = "UPDATE mail_providers
            SET recent_day_usage = 0, recent_hour_usage = 0
            WHERE DATEDIFF(CURRENT_DATE, recent_usage_date) >= 1;
            UPDATE mail_providers
            SET recent_hour_usage = 0
            WHERE HOUR(CURRENT_TIME) > HOUR(recent_usage_date);";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    []
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

    public function Create(array $params = []): Result
    {
        $sql = "INSERT INTO mail_providers
            (provider, smtp_host, smtp_port, username, password, send_from, day_usage_limit, hour_usage_limit, recent_day_usage, recent_hour_usage, total_usage, recent_usage_date, provider_status)
            VALUES(:provider, :smtp_host, :smtp_port, :username, :send_from, :password, :day_usage_limit, :hour_usage_limit, :recent_day_usage, :recent_hour_usage, :total_usage, :recent_usage_date, :provider_status)";

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
        $resReset = $this->ResetCounters();

        if(is_null($resReset->data)){
            return $resReset;
        }

        $sql = "SELECT provider_id, provider, smtp_host, smtp_port, username, password, send_from, day_usage_limit, hour_usage_limit, recent_day_usage, recent_hour_usage, (day_usage_limit - recent_day_usage) AS day_capacity, (hour_usage_limit - recent_hour_usage) AS hour_capacity, total_usage, recent_usage_date, provider_status
            FROM mail_providers
            WHERE provider_status = 'Active' AND recent_day_usage < day_usage_limit AND recent_hour_usage < hour_usage_limit
            ORDER BY recent_day_usage, recent_hour_usage;";

        $rowsets = [];
        try {
            $rowsets = $this->query($sql, []);
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
                L::loc('Failed to read {object}', '', ['object' => 'mail providers']),
                'error'
            );
        }

        return new Result(
            $rowsets
        );
    }
    
    public function UpdateUsageCounters(array $params = []): Result
    {
        $sql = "UPDATE mail_providers
            SET
                recent_day_usage = recent_day_usage + :sent_count,
                recent_hour_usage = recent_hour_usage + :sent_count,
                total_usage = total_usage + :sent_count,
                recent_usage_date = NOW()
            WHERE provider_id = :provider_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['provider_id']
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

    public function Update(array $params = []): Result
    {
        $sql = "UPDATE mail_providers
            SET
                provider = :provider,
                smtp_host = :smtp_host,
                smtp_port = :smtp_port,
                username = :username,
                password = :password,
                send_from = :send_from,
                day_usage_limit = :day_usage_limit,
                hour_usage_limit = :hour_usage_limit,
                recent_day_usage = :recent_day_usage,
                recent_hour_usage = :recent_hour_usage,
                total_usage = :total_usage,
                recent_usage_date = :recent_usage_date,
                provider_status = :provider_status
            WHERE provider_id = :provider_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['provider_id']
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
        $sql = "DELETE FROM mail_providers WHERE provider_id = :provider_id;";

        try {
            if($this->query($sql, $params)){
                return new Result(
                    $params['provider_id']
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