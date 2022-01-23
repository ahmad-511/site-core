<?php
declare (strict_types = 1);
namespace App\Core;

class Validator {
    private $object = [];
    private $validations = [];

    public function __construct(&$object)
    {
        $this->object = &$object;
        $this->validations = [];
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
     * Remove validation rule
     * @param string $prop Model's property to validate
     * @return void
     */
    public function remove(string $prop): void{
        $this->validations = array_filter($this->validations, function(array $rule)use($prop){
            if($rule['prop'] == $prop){
                return false;
            }

            return true;
        });
    }

    /**
     * This will allow us to read all class properties even private/protected ones
     * @param object $object object to be converted to array
     * @return array array of object properties
     */
    private function objectToArray(object $object):array{
        $className = get_class($object);
        $classNameLen = strlen($className);

        $object = (array)$object;
        $keys = array_keys($object);
        $keys = array_map(function($k)use($classNameLen){
            return substr($k, $classNameLen + 2); // there are 2 spaces surrounding class name in the $k
        }, $keys);

        // Converting object properties to an associative array
        return array_combine($keys, array_values($object));
    }

    private function isValid(array $object, array $props, string $checkType = 'exclude'):array{
        $validity = [];

        foreach($this->validations as ['prop' => $prop, 'message' => $message, 'validationFunc' => $validationFunc]){
            if($checkType == 'exclude' && in_array($prop, $props)){
                continue;
            }elseif($checkType == 'include' && !in_array($prop, $props)){
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

    /**
     * Check all but specified validation rules
     * @param array $excludeProps Array of properties to be excluded while validating
     * @return array Associative array of invalid property/message as [prop=>message]
     */
    public function validate(array $excludeProps = []):array{
        $object = $this->object;

        // Convert object to associative array
        if(gettype($object) == 'object'){
            $object = $this->objectToArray($object);
        }

        return $this->isValid($object, $excludeProps, 'exclude');
    }

    /**
     * Check only specified validation rules
     * @param array $includeProps Array of properties to be included while validating
     * @return array Associative array of invalid property/message as [prop=>message]
     */
    public function validateOnly(array $includeProps = []):array{
        $object = $this->object;

        // Convert object to associative array
        if(gettype($object) == 'object'){
            $object = $this->objectToArray($object);
        }

        return $this->isValid($object, $includeProps, 'include');
    }
}
?>