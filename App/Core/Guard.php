<?php
declare (strict_types = 1);
namespace App\Core;

use App\Core\Auth;

class GuradResult{
    public bool $canAccess;
    public string $redirectCode;

    public function __construct($canAccess = true, $redirectCode = '')
    {
        $this->canAccess = $canAccess;
        $this->redirectCode = $redirectCode;
    }
}

class Guard {
    /**
     * Check if current user can access specified view
     * @param string $viewCode View code
     * @return GuardResult
     */
    public static function canAccess($viewCode)
    {
        $loginRequired = [
            'dashboard',
            'user-manager',
        ];

        $result = new GuradResult();

        if(!Auth::isLoggedIn() && in_array($viewCode, $loginRequired)){
            return new GuradResult(false, 'login');
        }
        
        return $result;
    }
    
    /**
     * Check if current user is allowed to execute sepecified model's method
     * @param string $model Model name
     * @param string $method Model's method name
     * @return bool whether or not execution is allowed
     */
    public static function methodAllowed($model, $method)
    {
        $loggedInOnlyMethods = [
            'User|Create',
            'User|Read',
            'User|Edit',
            'User|Delete',
        ];

        if(!Auth::isLoggedIn() && in_array("$model|$method", $loggedInOnlyMethods, true)){
            return false;
        }

        return true;
    }
}
?>