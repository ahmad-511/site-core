<?php
declare (strict_types = 1);
namespace App\Core;

use App\Core\Auth;

class GuradResult{
    public bool $isAllowed;
    public string $redirectFileName;
    public string $message;

    public function __construct(bool $isAllowed = true, string $redirectFileName = '', string $message = '')
    {
        $this->isAllowed = $isAllowed;
        $this->redirectFileName = $redirectFileName;
        $this->message = $message;
    }
}

class Guard {
    /**
     * Check if current user can access specified view
     * @param string $routeName Route name
     * @return GuardResult
     */
    public static function canView($routeName): GuradResult
    {
        // View names that require admin privileges 
        $adminOnly = [];

        // View names that must be disabled in certain conditions
        $disabledViews = [];

        if(in_array($routeName, $disabledViews)){
            return new GuradResult(false, 'page-not-found');
        }

        if(in_array($routeName, $adminOnly) && !(Auth::authenticated() && Auth::getUser('account_type') == 'Admin')){
            return new GuradResult(false, 'login', "This is an admin's area only");
        }
        
        return new GuradResult();
    }
    
    /**
     * Check if current account is allowed to execute sepecified controller's method
     * @param string $controller Controller name
     * @param string $method Controller's method name
     * @return GuradResult
     */
    public static function canExecute($controller, $method): GuradResult
    {
        $controller = str_replace('App\\Controller\\', '', $controller);

        // Admin only controller methods
        $adminOnly = [
            'AccountController::Create',
            'AccountController::Read',
            'AccountController::List',
            'AccountController::Update',
            'AccountController::Delete',
            'AccountController::SendVerificationEmail',
            'AccountController::SendVerificationSMS',
        ];
        
        // Controller methods that must be disabled in certain conditions
        $disabledMethods = [];
        
        if(in_array("$controller::$method", $disabledMethods)){
            return new GuradResult(false, '', 'Not found');
        }

        if(in_array("$controller::$method", $adminOnly) && !(Auth::authenticated() && Auth::getUser('account_type') == 'Admin')){
            return new GuradResult(false, '', 'Only admin can call this action');
        }

        return new GuradResult();
    }
}
?>