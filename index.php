<?php
declare (strict_types = 1);

use App\Core\Request;
use App\Core\Router;

require_once __DIR__ . '/App/Core/bootstrap.php';

function checkMaintenanceMode(){
    $maintenancePass = (Request::body())['password']??'';

    if($maintenancePass == MAINTENANCE_PASSWORD) {
        $_SESSION['bypass_maintenance'] = true;
    }

    $bypassMaintenance = $_SESSION['bypass_maintenance']??false;


    if(MAINTENANCE_MODE && !$bypassMaintenance){
        include __DIR__ . '/under-constructions.php';
        exit();
    }
}

checkMaintenanceMode();

// Add custom routing if needed
include "custom-routes.php";

Router::resolve();
