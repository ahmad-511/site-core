<?php
declare (strict_types = 1);
namespace App\Core;

class Request
{
    private static array $Locales = ['en'];

    /**
     * Set accepted locale codes
     * @param array $localeCodes Array of accepted language locale codes the path can use to execlude language locale code from the URI if needed (getURISegments)
     * @return void
     */
    public static function setLocales(array $localeCodes){
        self::$Locales = $localeCodes;
    }

    /**
     * Get current request method
     * @return string Request method (GET, POST,...)
     */
    public static function getMethod(){
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return string The path part of the current request
     */
    public static function getPath(){
        return parse_url($_SERVER['REQUEST_URI'] ?? '')['path'];
    }

    /**
     * Get request sigments (sigments are uri part separated by uri separator)
     * @param bool $ignoreLocale don't include locale sigment when true
     * @return array Array of uri segements
     */
    public static function getURISegments($ignoreLocale = true)
    {
        $rp = self::getPath();
        $rp = trim($rp, '/');
        $segs = explode('/', $rp);

        // By default don't include locale param in segments array
        if ($ignoreLocale) {
            if (in_array(strtolower($segs[0]), self::$Locales)) {
                array_shift($segs);
            }
        }

        return $segs;
    }

    /**
     * @return array GET params
     */
    public static function getQueryParams()
    {
        return $_GET;
    }

    /**
     * Get data payload from $_POST or from input stream when content type is json
     * @return array POST params
     */
    public static function body(){
        static $cache = null;
        
        if(!empty($cache)){
            return $cache;
        }
        
        $isJSONContent = strtolower(getallheaders()['Content-Type']??'') == 'application/json';

        if($isJSONContent){
            $data = json_decode(file_get_contents('php://input'), true);
            // Make sure input stream is successfully decoded
            if(!is_null($data)){
                $_POST = $data;
            }
        }
        
        $cache = $_POST;
        return $_POST;
    }
}
