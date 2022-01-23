<?php
declare (strict_types = 1);

namespace App\Service;

use App\Core\Service;
use App\Model\Notification;
use App\Core\DB;

class NotificationService extends Service
{
    private static Notification $notification;

    public static function Send(int $accountID, int $importance = 1, string $notification, array $params = [], string $notificationLink = ''){
        self::$notification = new Notification();

        $resCreate = self::$notification->Create([
            'account_id' => $accountID,
            'importance' => $importance,
            'notification' => $notification,
            'params' => json_encode($params),
            'notification_link' => $notificationLink,
            'notification_status' => 'New',
            'create_date' => date('Y-m-d H:i:s')
        ]);

        // DB Error
        if(is_null($resCreate->data)){
            return null;
        }

        return $resCreate->data;
    }

    public static function SendMultiple(array $notifs = []):Array
    {
        $data = [];
        
        DB::beginTransaction();
        foreach($notifs as [$accountID, $importance, $notification, $params, $notificationLink]){
            $res = self::Send(intval($accountID), intval($importance), $notification, $params, $notificationLink);

            if(is_null($res)){
                DB::RollBack();
                return null;
            }

            $data[] = $res;
        }
        DB::commit();

        return $data;
    }
}