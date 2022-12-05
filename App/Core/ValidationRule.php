<?php
declare (strict_types = 1);
namespace App\Core;

class ValidationRule {
    
    /**
     * Check if value in the specified list
     * @param array $list List of accepted values
     * @return callable
     */
    public static function inList(array $list = []){
        return function($value) use($list){
            return in_array($value, $list);
        };
    }

    /**
     * Check if value is array
     * @param array $keys List of required keys
     * @return callable
     */
    public static function arrayOf(array $keys = [], bool $allowEmpty = false){
        return function($value) use($keys, $allowEmpty){
            if(!is_array($value)){
                return false;
            }

            if(empty($value)){
                return $allowEmpty?true: false;
            }

            // $value should not be empty at this stage
            // Check first item for specified keys
            foreach($keys as $k){
                if(!array_key_exists($k, $value[0])){
                    return false;
                }
            }

            return true;
        };
    }

    /**
     * Check if value is array
     * @param array $props List of required props
     * @return callable
     */
    public static function object(array $props = []){
        return function($value) use($props){
            $value = (array)$value;

            // Check first item for specified props
            foreach($props as $k){
                if(!array_key_exists($k, $value)){
                    return false;
                }
            }

            return true;
        };
    }

    /**
     * Check if value is a boolean
     * @return callable
     */
    public static function boolean(){
        return function($value){
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) !== false;
        };
    }

    /**
     * Check if value is an integer
     * @param int $min (optional) the minimum accepted value
     * @param int $max (optional) the maximum accepted value
     * @return callable
     */
    public static function int(int $min = PHP_INT_MIN, int $max = PHP_INT_MAX){
        return function($value)use($min, $max){
            return filter_var($value, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => $min, 'max_range' => $max))) !== false;
        };
    }

    /**
     * Check if value is a number
     * @param int $min (optional) the minimum accepted value
     * @param int $max (optional) the maximum accepted value
     * @return callable
     */
    public static function number(float $min = 0, float $max = PHP_INT_MAX){
        return function($value)use($min, $max){
            return filter_var($value, FILTER_VALIDATE_FLOAT, array( 'options' => array( 'min_range' => $min, 'max_range' => $max))) !== false;
        };
    }

    /**
     * Check if value is a string
     * @param int $min (optional) the minimum accepted length
     * @param int $max (optional) the maximum accepted length
     * @return callable
     */
    public static function string(int $min = 0, int $max = PHP_INT_MAX){
        return function($value)use($min, $max){
            $len = mb_strlen($value);
            return is_string($value) && $len >= $min && $len <= $max;
        };
    }

    /**
     * Check if value is a json string
     * @return callable
     */
    public static function json(){
        return function($value){
            json_decode($value);
            return (json_last_error() == JSON_ERROR_NONE);
        };
    }

    /**
     * Check if value is noe empty
     * @return callable
     */
    public static function notEmpty(){
        return function($value){
            return !empty($value);
        };
    }

    /**
     * Check if value is a date
     * @return callable
     */
    public static function date(){
        return function($value){
            $res = date_parse($value);
            return $res !== false && $res['error_count'] == 0 && $res['year'] !== false && $res['month'] !== false && $res['day'] !== false;
        };
    }

    /**
     * Check if value is a time
     * @return callable
     */
    public static function time(){
        return function($value){
            $res = date_parse($value);
            return $res !== false && $res['error_count'] == 0 && $res['hour'] !== false && $res['minute'] !== false && $res['second'] !== false;

        };
    }

    /**
     * Check if value is an email
     * @param bool $acceptEmpty (optional) Accept empty string
     * @return callable
     */
    public static function email(bool $acceptEmpty = false){
        return function($value)use($acceptEmpty){
            if(empty($value) && $acceptEmpty){
                return true;
            }

            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        };
    }

    /**
     * Check if value is a url
     * @param bool $acceptEmpty (optional) Accept empty string
     * @return callable
     */
    public static function url(bool $acceptEmpty = false){
        return function($value)use($acceptEmpty){
            if(empty($value) && $acceptEmpty){
                return true;
            }

            return filter_var($value, FILTER_VALIDATE_URL) !== false;
        };
    }

    /**
     * Check if value matches passed regular expression
     * @param string $regexp Regular expression to test the va;ue against
     * @return callable
     */
    public static function regexp($regexp){
        return function($value) use($regexp){
            return filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => $regexp]]) !== false;
        };
    }
}