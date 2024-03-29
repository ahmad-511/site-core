<?php

declare (strict_types = 1);

namespace App\Service;

use App\Core\Response;
use App\Core\Result;
use App\Core\Service;
use App\Service\SMSServiceInterface;

class FakeSMSService extends Service implements SMSServiceInterface
{
    public static function GetVirtualNumber():string{
        return '+9988776655';
    }

    public static function Send($recipientNumber, $message):Result
    {
        // E.164 Number format is required
        $recipientNumber = str_replace([' ', '+', '-', '(', ')'] , '', ltrim($recipientNumber, '0'));

        return new Result(
            $recipientNumber,
            'Message sent successfully',
            'success'
        );
    }

    public static function ValidateWebhook(array $payload = []): Result
    {
        $data = [
            'message_id' => '',
            'type' => '',
            'from' => '',
            'text' => '',
            'timestamp' => 0
        ];

        return new Result(
            null, // $data
            'Not implemented',
            'error'
        );
    }

    public static function GetSuccessResponse(array $payload = [])
    {
        Response::setStatus(200);
        return null;
    }
}

?>