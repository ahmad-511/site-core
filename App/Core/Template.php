<?php
namespace App\Core;

use App\Core\Path;
use App\Core\ExpressionParser;

class Template{
    private static string $TemplatesDir = '/templates';
    private static array $GeneralParams = [];
    private static string $DefaultExtension = '.html';
    private static string $DefaultLocale = '';
    private static bool $CacheEnabled = false;
    private static array $TemplateCache = [];
    private string $template = '';
    private string $currentLocale = '';

    /**
     * Create template object from specified file name and language code
     * @param string $template Template file name using default extension (.html)
     * @param string $locale Language locale code
     */
    public function __construct(string $template = '', string $locale = '')
    {
        if(empty($locale)){
            $locale = self::$DefaultLocale;
        }

        $this->currentLocale = $locale;
        $this->template = '';

        if(empty($template)){
            return;
        }

        $contents = self::LoadTemplate($template, $locale);
        $this->setTemplate($contents, $locale);
    }

    /**
     * Set template body string manually
     * @param string $contents, template body string
     * @param string $locale, language code necessary for including sub templates
     * @return void
     */
    public function setTemplate(string $contents, string $locale = ''){
        $this->template = self::ParseIncludes($contents, $locale);
    }

    /**
     * Merge included sub template 
     */
    public static function ParseIncludes(string $contents, string $locale): string
    {
        preg_match_all("#@include\s+?(.+)\s?#i", $contents, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return $contents;
        }

        for ($i = 0; $i < count($matches); $i++) {
            $match = $matches[$i];
            $include = $match[0];
            $subTemplate = trim($match[1]);
            
            $subTemplate = self::LoadTemplate($subTemplate, $locale);
            $contents = str_replace($include, $subTemplate, $contents);
        }

        return $contents;
    }

    private static function LoadTemplate(string $template, string $locale){
        // Load from cached templates if enabled
        if(self::$CacheEnabled && array_key_exists($template . '|' . $locale, self::$TemplateCache)){
            return self::$TemplateCache[$template . '|' . $locale];
        }

        // Load from file
        $path = Path::getLocalePath(self::$TemplatesDir, $template . self::$DefaultExtension, $locale);
        
        if (file_exists($path)) {
            $contents = file_get_contents($path);

            if(self::$CacheEnabled){
                self::$TemplateCache[$template . '|' . $locale] = $contents;
            }
        }else{
            return 'Template not found: ' . $path;
        }

        return $contents;
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
     * Enable / Disable caching
     * @param bool $isEnabled
     * @return void
     */
    public static function EnableCaching(bool $isEnabled){
        self::$CacheEnabled = $isEnabled;
    }

    /**
     * Get template caching state
     * @return bool caching state
     */
    public static function IsCaching(): bool{
        return self::$CacheEnabled;
    }

    /**
     * Clear template cache
     */
    public static function ClearCache(){
        self::$TemplateCache = [];
    }

    /**
     * Render loaded template contents
     * @param array $params Array of locale code => array of name value params used inside the template
     * @return string Template file contents with Both GeneralParams and $params placeholder being replaced 
     */
    public function render(array $params = [])
    {
        $generalParams = [];

        // Get global params
        if(array_key_exists('', self::$GeneralParams)){
            $generalParams = self::$GeneralParams[''];
        }

        // Get locale specific from general params according to current locale
        if(array_key_exists($this->currentLocale, self::$GeneralParams)){
            $generalParams = array_merge($generalParams, self::$GeneralParams[$this->currentLocale]);
        }elseif(array_key_exists($this->getDefaultLocale(), self::$GeneralParams)){
            $generalParams = array_merge($generalParams, self::$GeneralParams[$this->getDefaultLocale()]);
        }        

        // Merge template specific params with general params that might be used in the template
        $params = array_merge($generalParams, $params);

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
    
        // Parse expressions
        $tpl = $this->parseExpressions($tpl, $params);

        // Replace array variables (Repeat)
        $tpl = $this->parseRepeat($tpl, $params);
    
        return $tpl;
    }

    private function parseExpressions($str){
        // $str =  $$str;
        // return $str;
        preg_match_all('#\{\{(.*?)\}\}#sim', $str, $matches, PREG_SET_ORDER);
        if($matches){
            foreach($matches as $match){
                $exp = str_replace("\n", '', trim($match[1]));
                $exp = ExpressionParser::parse($exp);
                $str = str_replace($match[0], $exp, $str);
            }
        }

        return $str;
    }

    private function parseRepeat($str, $params){
        preg_match_all("#\[repeat\s+?(.+?)=>(.+?)\](.*?)\[/repeat\]#si", $str, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return $str;
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
            $str = str_replace($match[0], $dataRepeat, $str);
        }

        return $str;
    }
}
?>