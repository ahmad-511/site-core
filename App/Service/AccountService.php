<?php
declare (strict_types = 1);

namespace App\Service;

use App\Core\Service;
use App\Model\Account;

class AccountService extends Service
{
    private static Account $account;

    public static function GetAccount(int $accountID){
        self::$account = new Account();

        $resAccount = self::$account->Read(['account_id' => $accountID]);
        if(empty($resAccount->data)){
            return null;
        }

        return $resAccount->data[0];
    }

    public static function generateEmailVerification(string $email): string{
        return md5($email . microtime() . date('YmdHis'));
    }

    public static function generateMobileVerification(): string{
        return (string) random_int(10000, 99999);
    }
}