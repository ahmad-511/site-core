<?php
declare (strict_types = 1);
namespace App\Core;

class Path{
    private static string $DefaultLocale = '';

    /**
     * Set default language locale code
     * @param string $localeCode
     * @return void
     */
    public static function setDefaultLocale(string $localeCode = ''){
        self::$DefaultLocale = $localeCode;
    }

    /**
     * Get default language locale code
     * @return string Default language locale code
     */
    public static function getDefaultLocale(){
        return self::$DefaultLocale;
    }

    /**
     * Determine the actual file path according to specified locale language code
     * @param string $dir File's base directory
     * @param string $file File name
     * @param string $lang locale language code
     * @return string The file path of the localized version
     */
    public static function getLocalePath($dir, $file, $lang = ''){
        // Use defaul langauge code if not specified
        if(empty($lang)){
            $lang = self::$DefaultLocale;
        }

        // Make sure we have consistent directory separator
        $dir = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $dir);

        if(substr($dir, -1, 1) != DIRECTORY_SEPARATOR){
            $dir .= DIRECTORY_SEPARATOR;
        }

        if(substr($file, 1, 1) == DIRECTORY_SEPARATOR){
            $file = substr($file, 1);
        }

        // Use specified dir to load the file if no language code is detected
        if(empty($lang)){
            $path = $dir . $file;
        }else{
            // Use specified dir + language code as base directory for the file
            $lang .= DIRECTORY_SEPARATOR;

            $path = $dir . $lang . $file;
        }

        // Fallback to default path if locale version doesn't exist
        if (!file_exists($path)) {
            $path = $dir . $file;
        }

        return $path;
    }

    /**
     * Creat path from path sigments
     * @param array $sigments Array of path sigments
     * @return string Path combined from sigments joined by the system directory separator
     */
    public static function combine(...$segmenets){
        $segmenets = array_map(function($seg){
            $seg = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $seg);
            $seg = trim($seg, DIRECTORY_SEPARATOR);

            return $seg;
        }, $segmenets);

        return implode(DIRECTORY_SEPARATOR, $segmenets);
    }
}
