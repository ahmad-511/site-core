<?php

declare (strict_types = 1);

namespace App\Service;

use App\Core\Result;

interface SMSServiceInterface {
    public static function GetVirtualNumber():string;
    public static function Send(string $recipientNumber, string $message):Result;
    public static function ValidateWebhook(array $payload):Result;
    public static function GetSuccessResponse(array $payload);
}

