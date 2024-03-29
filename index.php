<?php
declare (strict_types = 1);

use App\Controller\AccountController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Router;

require_once __DIR__ . '/App/Core/bootstrap.php';

function checkMaintenanceMode(){
    $maintenancePass = (Request::body())['password']??'';

    if($maintenancePass == MAINTENANCE_PASSWORD) {
        $_SESSION['bypass_maintenance'] = true;
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    $bypassMaintenance = $_SESSION['bypass_maintenance']??false;


    if(!$bypassMaintenance){
        include __DIR__ . '/under-constructions.php';
        exit();
    }
}

if(MAINTENANCE_MODE && !($_SESSION['bypass_maintenance']??false)){
    checkMaintenanceMode();
}

// Remember me cookie
if(ENABLE_REMEMBER_ME && !Auth::authenticated() && !empty($_COOKIE[REMEMBER_ME_COOKIE_NAME])){
    $accountCtrl = new AccountController();
    $accountCtrl->CookieLogin([]);
}

Router::resolve();
