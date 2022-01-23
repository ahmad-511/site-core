<?php
declare (strict_types = 1);
namespace App\Core;

class Auth{
    public static string $AuthSession = 'user';
    public static string $AuthUserId = 'user_id';

    /**
     * Check if current user is logged in
     * @return bool, true if logged in, false otherwise
     */
    public static function authenticated():bool
    {
        if(empty($_SESSION[self::$AuthSession]??'')){
            return false;
        }

        return intval($_SESSION[self::$AuthSession][self::$AuthUserId]??0) > 0;
    }

    /**
     * Destroy user auth data
     * @return bool, true
     */
    public static function destroyUser():bool
    {
        if(self::authenticated()){
            $_SESSION[self::$AuthSession] = null;
            session_destroy();
        }

        return true;
    }
    
    /**
     * Set logged in user information
     */
    public static function setUser($userInfo){
        $_SESSION[self::$AuthSession] = $userInfo;
    }
    
    /**
     * Get user information, all or for specified property
     * @param string|empty $prop user info property
     * @return array|string|empty all or specific user info or empty string if user not logged in
     */
    public static function getUser($prop = '')
    {
        if(empty($_SESSION[self::$AuthSession]??'')){
            return '';
        }
    
        return empty($prop)?$_SESSION[self::$AuthSession]: $_SESSION[self::$AuthSession][$prop]??'';
    }
}