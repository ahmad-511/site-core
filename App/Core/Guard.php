<?php
declare (strict_types = 1);
namespace App\Core;

use App\Core\Auth;

class GuradResult{
    public bool $isAllowed;
    public string $redirectCode;

    public function __construct($isAllowed = true, $redirectCode = '')
    {
        $this->isAllowed = $isAllowed;
        $this->redirectCode = $redirectCode;
    }
}

class Guard {
    /**
     * Check if current user can access specified view
     * @param string $viewCode View code
     * @return GuardResult
     */
    public static function canView($viewCode): GuradResult
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
     * Check if current account is allowed to execute sepecified controller's method
     * @param string $controller Controller name
     * @param string $method Controller's method name
     * @return bool whether or not execution is allowed
     */
    public static function canExecute($controller, $method)
    {
        $loggedInOnlyMethods = [
            'Account|Create',
            'Account|Read',
            'Account|Edit',
            'Account|Delete',
        ];

        if(!Auth::isLoggedIn() && in_array("$controller|$method", $loggedInOnlyMethods, true)){
            return false;
        }

        return true;
    }
}
?>