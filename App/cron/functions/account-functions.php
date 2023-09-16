<?php
declare (strict_types = 1);
namespace App\Cron\AccountFunctions;

use App\Core\Localizer as L;
use App\Core\Crypto;
use App\Core\Router;
use App\Model\Account;
use App\Service\MailService;

require_once __DIR__ . '/../../Core/bootstrap.php';

// Set account status to Warning and return a list of warned accounts
function WarnPending(int $pendingDays = 10, int $extendedDays = 5){
    $accountModel = new Account();
    $resPending = $accountModel->WarnPending([
        'days_period' => $pendingDays
    ]);

    foreach($resPending->data as $a){
        $a['name'] = $a['name'] . ' ' . $a['surname'];
        $a['pending_days'] = $pendingDays;
        $a['extended_days'] = $extendedDays;

        // Encrypt account id with verification code to be used in verification link
        $verificationCode = Crypto::Encrypt($a['account_id'] . ';' . $a['email_verification']);
        $a['email_verification_view_url'] = WEBSITE_URL . Router::route('verify-my-mobile-view', ['verification_code' => $verificationCode], $a['preferred_language'], false);
        $a['mobile_verification_view_url'] = WEBSITE_URL . Router::route('verify-my-mobile-view', [], $a['preferred_language'], false);
        $a['login_view_url'] = WEBSITE_URL . Router::route('login-view', [], $a['preferred_language'], false);
        
        $messages[] = [
            $a['email'],
            L::loc('Your account is subject to deletion', $a['preferred_language']),
            'email-deletion-warning', 
            $a,
            true,
            0,
            $a['preferred_language']
        ];
    }
    
    $mailResult = [];
    if(!empty($messages)){
        $mailResult = MailService::SendMultiple($messages);
    }

    return $mailResult;
}

function DeleteWarned(int $pendingDays = 15){
    $accountModel = new Account();
    $resDelete = $accountModel->DeleteWarned([
        'days_period' => $pendingDays
    ]);

    foreach($resDelete->data as $a){
        $a['name'] = $a['name'] . ' ' . $a['surname'];
        $a['pending_days'] = $pendingDays;
        
        $messages[] = [
            $a['email'],
            L::loc('Your account has been deleted', $a['preferred_language']),
            'email-deletion', 
            $a,
            true,
            0,
            $a['preferred_language']
        ];
    }
    
    $mailResult = [];
    if(!empty($messages)){
        $mailResult = MailService::SendMultiple($messages);
    }

    return $mailResult;
}

?>