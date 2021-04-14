<?php
declare (strict_types = 1);
namespace App\Core;

class Auth{
    /**
     * Check if current user is logged in
     * @return bool, true if logged in, false otherwise
     */
    public static function isLoggedIn(){
        return !empty($_SESSION['user']??'');
    }
    
    /**
     * Set logged in user information
     */
    public static function setUser($userInfo){
        $_SESSION['user'] = $userInfo;
    }
    
    /**
     * Get user information for specified property
     * @param string $prop user info property
     * @return string|empty user info or empty string if user not logged in
     */
    public static function getUser($prop){
        if(!self::isLoggedIn()){
            return '';
        }
    
        return $_SESSION['user'][$prop]??'';
    }
    
    /** 
     * Get user display name
     * @return string User display name
     */
    public static function getUserName(){
        if(!self::isLoggedIn()){
            return '';
        }
    
        return $_SESSION['user']['display_name'];
    }
    
    /**
     * Get user initials from user display name
     * @param string user display name
     * @return string User's initials (one or two letters)
     */
    public static function getNameInitials($name){
        if(empty($name)){
            return $name;
        }
    
        $name = str_replace('  ', ' ', $name);
        $parts = explode(' ', $name);
    
        if(count($parts) == 1){
            return substr($parts[0], 0, 1);
        }
    
        if(count($parts) > 1){
            return substr($parts[0], 0, 1) . substr($parts[count($parts)-1], 0, 1);
        }
    }
}