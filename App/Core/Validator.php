<?php
namespace App\Core;

class Validator {
    private $object = [];
    private $validations = [];

    public function __construct($object)
    {
        $this->object = $object;
        $this->validations = [];
    }

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
     * Check if value is a boolean
     * @return callable
     */
    public static function boolean(){
        return function($value){
            return filter_var($value, FILTER_VALIDATE_BOOL) !== false;
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
     * Check if value is a positive number
     * @param int $min (optional) the minimum accepted value
     * @param int $max (optional) the maximum accepted value
     * @return callable
     */
    public static function positive(float $min = 0, float $max = PHP_INT_MAX){
        return function($value)use($min, $max){
            return filter_var($value, FILTER_VALIDATE_FLOAT, array( 'options' => array( 'min_range' => $min, 'max_range' => $max))) !== false;
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
            return date_parse($value) !== false;
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
            filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => $regexp]]) !== false;
        };
    }

    /**
     * Add validation rule
     * @param string $prop Model's property to validate
     * @param string $message Message when property is invalid
     * @param callable $validationFunc One of Validator's validation function OR user custom ones
     * @return void
     */
    public function add(string $prop, string $message, callable $validationFunc): void{
        $this->validations[] = [
            'prop' => $prop,
            'message' => $message,
            'validationFunc' => $validationFunc
        ];
    }

    /**
     * Check all added validation rules
     * @param array $excludeProps Array of properties to be excluded while validating
     * @return array Associative array of invalid property/message as [prop=>message, ]
     */
    public function validate(array $excludeProps = []):array{
        $validity = [];

        $object = $this->object;

        // This will allow us to read all class properties even private/protected ones
        if(gettype($object) == 'object'){
            $className = get_class($object);
            $classNameLen = strlen($className);

            $object = (array)$object;
            $keys = array_keys($object);
            $keys = array_map(function($k)use($classNameLen){
                return substr($k, $classNameLen + 2); // there are 2 spaces surrounding class name in the $k
            }, $keys);

            $object = array_combine($keys, array_values($object));
        }

        foreach($this->validations as ['prop' => $prop, 'message' => $message, 'validationFunc' => $validationFunc]){
            if(in_array($prop, $excludeProps)){
                continue;
            }

            $isValid = $validationFunc($object[$prop]);

            if(!$isValid){
                // Combine multiple validation messages for the same property if any
                if(isset($validity[$prop])){
                    $message = $validity[$prop] .', '. $message;
                }

                $validity[$prop] = $message;
            }
        }
        
        return $validity;
    }
}
?>