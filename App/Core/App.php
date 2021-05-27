<?php
declare (strict_types = 1);
namespace App\Core;

/** This class provides necessary functions used in different places through out the system **/
class App{
    /**
     * Used inside a layout file,
     * Include HTML page's meta tags (description, keywords, title, social og:title, og:description, og:image, og:url, twitter:card)
     * @param string $viewCode
     */
    public static function includeMeta($viewCode){
        $meta = require_once __DIR__ .'/../configs/page-meta-'.Router::getCurrentLocaleCode().'.php';
    
        if(!array_key_exists($viewCode, $meta)){
            return false;
        }
    
        $pageMeta = $meta[$viewCode];
    
        if(array_key_exists('description', $pageMeta)){
            echo '<meta name="description" content="', $pageMeta['description'], '">', "\n";
        }
    
        if(array_key_exists('keywords', $pageMeta)){
            echo '<meta name="keywords" content="', $pageMeta['keywords'], '">', "\n";
        }
    
        if(array_key_exists('title', $pageMeta)){
            echo '<title>', $pageMeta['title'], ' | ', WEBSITE_TITLE, '</title>', "\n";
        }else{
            echo '<title>', ucwords($viewCode), ' | ', WEBSITE_TITLE, '</title>', "\n";
        }

        // Social related tags
        if(array_key_exists('title', $pageMeta)){
            echo '<meta property="og:title" content="', $pageMeta['title'], ' | ', WEBSITE_TITLE, '">';
        }else{
            echo '<meta property="og:title" content="', ucwords($viewCode), ' | ', WEBSITE_TITLE, '">';
        }

        if(array_key_exists('description', $pageMeta)){
            echo '<meta property="og:description" content="', $pageMeta['description'], '">';
        }

        if(array_key_exists('image', $pageMeta)){
            echo '<meta property="og:image" content="', $pageMeta['image'], '">';
        }
        
        if(array_key_exists('url', $pageMeta) && !empty($pageMeta['url'])){
            echo '<meta property="og:url" content="', $pageMeta['url'], '">';
        }else{
            echo '<meta property="og:url" content="', WEBSITE_URL , '/', strtoupper(Router::getCurrentLocaleCode()), '/', ucwords(Router::getCurrentViewCode()), '">';
        }

        if(array_key_exists('card', $pageMeta)){
            echo '<meta name="twitter:card" content="', $pageMeta['card'], '">';
        }
    }
    
    /**
     * Used inside a layout file,
     * Include page related files (css, js, js/module)
     * @param string $viewCode (viewCode)
     */
    public static function includeFiles($viewCode){
        $files = require_once __DIR__ .'/../configs/page-files.php';
    
        $pageFiles = [];
        if(array_key_exists('*', $files)){
            $pageFiles = $files['*'];
        }
    
        if(array_key_exists($viewCode, $files)){
            foreach($files[$viewCode] as $type => $items){
                if(array_key_exists($type, $pageFiles)){
                    $pageFiles[$type] = array_merge($pageFiles[$type], $items);
                }
            }
        }
    
        if(empty($pageFiles)){
            return false;
        }
    
        if(array_key_exists('css', $pageFiles)){
            foreach($pageFiles['css'] as $s){
                echo '<link rel="stylesheet" href="'.$s.'">'."\n";
            }
        }
    
        if(array_key_exists('js', $pageFiles)){
            foreach($pageFiles['js'] as $s){
                echo '<script src="'.$s.'"></script>'."\n";
            }
        }
    
        if(array_key_exists('module', $pageFiles)){
            foreach($pageFiles['module'] as $s){
                echo '<script type="module" src="'.$s.'"></script>'."\n";
            }
        }
    }
    
    /** 
     * Check if specified page code is currently selected one
     * @param string $pagecode (viewCode)
     * @return string selected | ''
     */
    public static function setSelectedPage($viewCode){
        return ($viewCode == Router::getCurrentViewCode())?'selected':'';
    }
    
    /**
     * Localize specified string using specified language (use App's current language if omitted)
     * @param string $str string to be localized
     * @param string|empty $lang language code
     * @return string Localized version of the input $str or the same $str if not found in specified language dictionary
     */
    public static function loc($str, $lang=''){
        static $dics=[];
    
        // Get currently used language
        if(empty($lang)){
            $lang = Router::getCurrentLocaleCode();
        }
    
        if(!array_key_exists($lang, $dics)){
            $path = __DIR__ .'/../dics/dic-'.$lang.'.json';
            
            // Load specific dictionary file if not already
            if(file_exists($path)){
                try{
                    $dics[$lang] = json_decode(file_get_contents($path), true);
                    
                    if(json_last_error()!==JSON_ERROR_NONE){
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
    
        // Return from specified dectionary
        if(array_key_exists($str, $dics[$lang])){
            return $dics[$lang][$str];
        }
    
        // Return from default dectionary
        if(array_key_exists(DEFAULT_LANGUAGE, $dics) && array_key_exists($str, $dics[DEFAULT_LANGUAGE])){
            return $dics[DEFAULT_LANGUAGE][$str];
        }
    
        // Return as is
        return $str;
    }
    
    /**
     * Get page record offset for pagination purposes
     * @param int $pageNum Page number
     * @return int Page's start record offset
     */
    public static function getPageOffset($pageNum){
        $pageNum = abs($pageNum??1);
        
        if($pageNum < 1){
            $pageNum = 1;
        }
        
        return ($pageNum - 1) * RECORDS_PER_PAGE;
    }

    /**
     * Get account initials from account full name
     * @param string account full name
     * @return string account's initials (one or two letters)
     */
    public static function getNameInitials($fullName){
        if(empty($fullName)){
            return $fullName;
        }
    
        $fullName = str_replace('  ', ' ', $fullName);
        $parts = explode(' ', $fullName);
    
        if(count($parts) == 1){
            return substr($parts[0], 0, 1);
        }
    
        if(count($parts) > 1){
            return substr($parts[0], 0, 1) . substr($parts[count($parts)-1], 0, 1);
        }
    }
}
?>