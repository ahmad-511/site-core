<?php
declare (strict_types = 1);
use App\Cron\AccountFunctions;

require_once __DIR__ . '/functions/account-functions.php';

$pendingDays = 10;
$extendedDays = 5;
AccountFunctions\WarnPending($pendingDays, $extendedDays);
AccountFunctions\DeleteWarned($pendingDays + $extendedDays);
?>