<?php
declare (strict_types = 1);

namespace App\Core;

use App\Core\Router;

class Localizer {

    private static $DictionaryDir = __DIR__ .'/../dics/';
    private static $DefaultLanguage = 'en';
    private static $AltLanguage = 'en';

    public static function setDictionaryDir(string $path)
    {
        if(str_ends_with($path, '/'))
        self::$DictionaryDir = $path;
    }

    public static function setDefaultLanguage(string $lang)
    {
        self::$DefaultLanguage = $lang;
    }

    public static function setAltLanguage(string $lang)
    {
        self::$AltLanguage = $lang;
    }

    /**
     * Localize specified string using specified language (use App's current language if omitted)
     * @param string $str string to be localized
     * @param string|empty $lang language code
     * @param array $params, associative array of param placeholder name/value
     * @return string Localized version of the input $str or the same $str if not found in specified language dictionary
     */
    public static function loc(string $str, string $lang = '', array $params = [], $variant = 0){
        static $dics=[];
    
        // Get currently used language
        if(empty($lang)){
            $lang = Router::getCurrentLocaleCode();
        }
    
        if(!array_key_exists($lang, $dics)){
            $path = self::$DictionaryDir . 'dic-' . $lang . '.json';
            
            // Load specific dictionary file if not already
            if(file_exists($path)){
                try{
                    $dics[$lang] = json_decode(file_get_contents($path), true);
                    
                    if(json_last_error() !== JSON_ERROR_NONE){
                        throw new \Exception('Dictionary decode error: '. json_last_error());
                    }
                }
                catch(\Exception $ex){
                    throw new \Exception('Dictionary load error: '. $ex->getMessage());
                }
            }else{
                $dics[$lang] = [];
            }
        }
    
        // Translating params
        $trParams = [];
        foreach($params as $k => $v){
            // If a translation was included in the params (using _alt suffix) then use it, otherwise use the dictionary
            if(array_key_exists("{$k}_alt", $params)){
                // If it is the alternative language then use param[$k_alt] version, otherwise use param[$k] ($v)
                $trParams[$k] = $lang == self::$AltLanguage?$params["{$k}_alt"]: $v;
            }else{
                $trParams[$k] = self::loc((string)$v, $lang);
            }
        }
        
        $params = null;

        // Check if string includes variant in it
        if(preg_match('#\[(.+?)\]$#', $str, $matches)){
            $str = str_replace($matches[0], '', $str);
            $variant = $matches[1];
        }

        // Get string from specified dictionary
        if(array_key_exists($str, $dics[$lang])){
            $val = $dics[$lang][$str];

            // Check for variants
            if(is_array($val)){
                if(array_key_exists($variant, $val)){
                    $str = $val[$variant];
                }else{
                    // Use first element if variant not exists
                    $fk = array_key_first($val);
                    if(!is_null($fk)){
                        $str = $val[$fk];
                    }
                }
            }else{
                $str = $val;
            }
        }elseif(array_key_exists(self::$DefaultLanguage, $dics) && array_key_exists($str, $dics[self::$DefaultLanguage])){
            // Get string from default dictionary
            $val = $dics[self::$DefaultLanguage][$str];

            // Check for variants
            if(is_array($val)){
                if(array_key_exists($variant, $val)){
                    $str = $val[$variant];
                }else{
                    // Use first element if variant not exists
                    $fk = array_key_first($val);
                    if(!is_null($fk)){
                        $str = $val[$fk];
                    }
                }
            }else{
                $str = $val;
            }
        }
    
        // Return str with all params replaced
        return self::replaceParams((string)$str, $trParams);
    }

    /**
     * Replace params
     * @param string $str, string containing one or more params placeholders
     * @param array $params, associative array of param placeholder name/value
     * @return string, string with params being replaced
     */
    public static function replaceParams(string $str, array $params = []){
        foreach($params as $k => $v){
            $str = str_replace('{' . $k .'}', $v, $str);
        }

        return $str;
    }

}
?>