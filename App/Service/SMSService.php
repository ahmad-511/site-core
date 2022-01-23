<?php

declare (strict_types = 1);

namespace App\Service;

use App\Core\Logger;
use App\Core\Result;
use App\Core\Service;
use App\Service\SMSServiceInterface;

class SMSService extends Service
{
    private static $Client;

    public static function SetSMSClient(SMSServiceInterface $client){
        static::$Client = $client;
    }

    public static function GetVirtualNumber():string{
        return static::$Client::GetVirtualNumber();
    }

    public static function Send(string $recipientNumber, string $message): Result
    {
        try{
            $result = static::$Client::Send($recipientNumber, $message);
            if($result->messageType == 'error'){
                Logger::log(print_r($result, true));
            }

            return $result;
        }
        catch(\Exception $ex){
            $error = $ex->getMessage();
            Logger::log($error);
            
            return new Result(
                null,
                $error,
                'error'
            );
        }
    }

    public static function ValidateWebhook(array $payload = []): Result
    {
        return static::$Client::ValidateWebhook($payload);
    }

    public static function GetSuccessResponse()
    {
        return static::$Client::GetSuccessResponse();
    }
}