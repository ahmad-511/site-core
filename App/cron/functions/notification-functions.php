<?php
declare (strict_types = 1);
namespace App\Cron\NotificationFunctions;

use App\Model\Notification;

require_once __DIR__ . '/../../Core/bootstrap.php';

function Cleanup(int $dayPeriod = 21){
    $notificationModel = new Notification();
    $setResult = $notificationModel->Cleanup([
        'day_period' => $dayPeriod
    ]);
    
    return $setResult;
}