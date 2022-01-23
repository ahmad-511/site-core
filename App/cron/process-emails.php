<?php
declare (strict_types = 1);
use App\Service\MailService;

require_once __DIR__ . '/../Core/bootstrap.php';

$resProcess = MailService::ProcessQueue();

print_r($resProcess);
?>