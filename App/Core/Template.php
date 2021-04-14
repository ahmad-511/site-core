<?php
namespace App\Core;

use App\Core\Path;

class Template{
    private static string $TemplatesDir = '/templates';
    private static array $GeneralParams = [];
    private static string $DefaultExtension = '.html';
    private static string $DefaultLocale = '';
    private string $template = '';

    /**
     * Create template object from specified file name and language code
     * @param string $template Template file name using default extension (.html)
     * @param string $lang Language locale code
     */
    public function __construct($template, $lang = '')
    {
        if(empty($lang)){
            $lang = self::$DefaultLocale;
        }

        $path = Path::getLocalePath(self::$TemplatesDir, $template . self::$DefaultExtension, $lang);

        if (file_exists($path)) {
            $this->template = file_get_contents($path);
        }
    
        $this->template = '';
    }

    /**
     * Set templates directory path on disk
     * @param string $templatesDir
     */
    public static function setTemplatesDir(string $templatesDir){
        self::$TemplatesDir = $templatesDir;
    }

    /**
     * Set template general params
     * @param array $generalParams
     */
    public static function setGeneralParams(array $generalParams){
        self::$GeneralParams = $generalParams;
    }

    /**
     * Set template files default extension
     * @param array $defaultExtension
     */
    public static function setDefaultExtension(string $defaultExtension){
        self::$DefaultExtension = $defaultExtension;
    }

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
     * Render loaded template contents
     * @param array $params Array of name value params used inside the template
     * @return string Template file contents with Both GeneralParams and $params placeholder being replaced 
     */
    public function render(array $params = [])
    {
        // Merge template specific params with general params that might be used in the template
        $params = array_merge(self::$GeneralParams, $params);

        $tpl = $this->template;

        // Replace flat variables
        foreach ($params as $k => $v) {
            if (!is_array($v)) {
                $tpl = str_replace('{' . $k . '}', $v, $tpl);
            } else {
                // Associative array
                foreach ($v as $k1 => $v1) {
                    if (!is_array($v1)) {
                        $tpl = str_replace('{' . $k . '.' . $k1 . '}', $v1, $tpl);
                    }
                };
            }
        }
    
        // Replace array variables (Repeat)
        preg_match_all("#\[repeat\s+?(.+?)=>(.+?)\](.*?)\[/repeat\]#s", $tpl, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return $tpl;
        }
    
        for ($i = 0; $i < count($matches); $i++) {
            $match = $matches[$i];
            $group = $match[1];
            $groupItem = $match[2];
            $segment = $match[3];
    
            $dataRepeat = [];
            // loop all data
            foreach ($params[$group] as $obj) {
                if (is_array($obj)) {
                    // Combine group item name with group item keys and enclose them within brackets
                    $objKeys = array_keys($obj);
                    $objKeys = array_map(function ($i) use ($groupItem) {
                        return '{' . $groupItem . '.' . $i . '}';
                    }, $objKeys);
    
                    $objValues = array_values($obj);
                    $dataRepeat[] = str_replace($objKeys, $objValues, $segment);
                } else {
                    $dataRepeat[] = str_replace('{' . $groupItem . '}', $obj, $segment);
                }
            }
    
            $dataRepeat = implode("", $dataRepeat);
            $tpl = str_replace($match[0], $dataRepeat, $tpl);
        }
    
        return $tpl;
    }
}
?>