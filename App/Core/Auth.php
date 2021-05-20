<?php
declare (strict_types = 1);
namespace App\Core;

class Auth{
    public static string $AuthSession = 'user';
    /**
     * Check if current user is logged in
     * @return bool, true if logged in, false otherwise
     */
    public static function isLoggedIn(){
        return !empty($_SESSION[self::$AuthSession]??'');
    }

    public static function destroyUser(){
        if(self::isLoggedIn()){
            unset($_SESSION[self::$AuthSession]);
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
     * @return string|empty user info or empty string if user not logged in
     */
    public static function getUser($prop = ''){
        if(!self::isLoggedIn()){
            return '';
        }
    
        return empty($prop)?$_SESSION[self::$AuthSession]: $_SESSION[self::$AuthSession][$prop]??'';
    }
}