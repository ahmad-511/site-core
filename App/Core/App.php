<?php
declare (strict_types = 1);
namespace App\Core;

use App\Core\Router;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Localizer as L;
use DateTime;

/** This class provides necessary functions used in different places through out the system **/
class App{
    /**
     * Used inside a layout file,
     * Include HTML page's meta tags (description, keywords, title, social og:title, og:description, og:image, og:url, twitter:card)
     * @param string $viewCode
     */
    public static function includeMeta($viewCode){
        $metaPath = realpath(__DIR__ .'/../configs/page-meta-'.Router::getCurrentLocaleCode().'.php');
        
        if(!file_exists($metaPath)){
            return false;
        }

        $meta = require_once $metaPath;
    
        if(!is_array($meta)){
            return false;
        }

        if(!array_key_exists($viewCode, $meta)){
            // This is all what we can do
            echo '<title>', ucwords(L::loc($viewCode)), ' | ', L::loc(WEBSITE_TITLE), '</title>', "\n";

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
            echo '<title>', $pageMeta['title'], ' | ', L::loc(WEBSITE_TITLE), '</title>', "\n";
        }else{
            echo '<title>', ucwords(L::loc($viewCode)), ' | ', L::loc(WEBSITE_TITLE), '</title>', "\n";
        }

        // Social related tags
        if(array_key_exists('title', $pageMeta)){
            echo '<meta property="og:title" content="', $pageMeta['title'], ' | ', L::loc(WEBSITE_TITLE), '">';
        }else{
            echo '<meta property="og:title" content="', ucwords(L::loc($viewCode)), ' | ', L::loc(WEBSITE_TITLE), '">';
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
            echo '<meta property="og:url" content="', WEBSITE_URL, Router::route(Router::getCurrentRouteName()), '">';
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
    
        if(!is_array($files)){
            return false;
        }
        
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
    
    public static function getURLforLanguage($lang){
        $qs = Request::getQueryString();
        if(!empty($qs)){
            $qs = "?$qs";
        }

        $params = Request::getURLSegments();
        if(!empty($params)){
            // Remove url segments that are parts of the route url ant not params
            $routePath = Router::route(Router::getCurrentRouteName(), [], $lang);
            $routeSegments = explode('/', $routePath);
            $params = array_reduce($params, function($acc, $curr) use($routeSegments){
                if(array_search($curr, $routeSegments) === false){
                    $acc[] = $curr;
                }
                return $acc;
            }, []);            
        }

        return Router::route(Router::getCurrentRouteName(), $params, $lang) . $qs ;
    }

    /** 
     * Check if specified page code is currently selected one
     * @param string $pagecode (viewCode)
     * @return string selected | ''
     */
    public static function setSelectedPage($viewCode){
        return ($viewCode == Router::getCurrentRouteName())?'selected':'';
    }
    
    /**
     * Convert client date to server date
     * @param DateTime $clientDate Date time as recieved from the client
     * @param int $clientOffset the time offset on the client machine
     * @return DateTime new client date according to server timezone 
     */
    public static function clientDate(DateTime $clientDate, int $clientOffset = 0):DateTime{
        $newDate = clone $clientDate;

        $minutes = abs($clientOffset);
        
        if($clientOffset > 0){
            $newDate->add(new \DateInterval("PT{$minutes}M"));
        }else{
            $newDate->sub(new \DateInterval("PT{$minutes}M"));
        }

        $serverDate = new \DateTime(date('Y-m-d H:i'));
        $serverOffset = $serverDate->getOffset();

        if($clientOffset > 0){
            $newDate->sub(new \DateInterval("PT{$serverOffset}S"));
        }else{
            $newDate->add(new \DateInterval("PT{$serverOffset}S"));
        }

        return $newDate;
    }

    /**
     * Get page's record limit
     * @param int $limit record limit
     * @param bool $forceLimit override RECORDS_PER_PAGE checking
     * @return int $limit if $limit < RECORDS_PER_PAGE, otherwise return $limit
     */
    public static function getPageLimit($limit = 0, bool $forceLimit = false){
        if($forceLimit){
            return ($limit > 0)?$limit: RECORDS_PER_PAGE;
        }

        return ($limit > 0 && $limit <= RECORDS_PER_PAGE)?$limit: RECORDS_PER_PAGE;
    }

    /**
     * Get page record offset for pagination purposes
     * @param int $pageNum Page number
     * @return int Page's start record offset
     */
    public static function getPageOffset($pageNum, $limit = RECORDS_PER_PAGE){
        $pageNum = abs(intval($pageNum)??1);
        
        if($pageNum < 1){
            $pageNum = 1;
        }
        
        return ($pageNum - 1) * $limit;
    }

    /**
     * Get account's full name
     * @return string account's initials (one or two letters)
     */
    public static function getAccountName(){
        if(!Auth::authenticated()){
                return '';
        }

        $account = Auth::getUser();

        return $account['name'];
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

    public static function stripEmail($str): string {
        return preg_replace('#(.+<)?(.+?)(>)?#', '${2}', $str); 
    }
    
    public static function isActiveUser(){
        return Auth::authenticated() && Auth::getUser('account_status') == 'Active';
    }

    public static function isAdmin(){
        return self::isActiveUser() && Auth::getUser('role') == 'Admin';
    }

    public static function load(string $filePath): string{
        $filePath = BASE_DIR . $filePath;

        // Tray localized version
        $lc = Router::getCurrentLocaleCode();
        if(!empty($lc)){
            $lcPath = str_replace('{locale}', $lc, $filePath);

            if(file_exists($lcPath)){
                return file_get_contents($lcPath);
            }

            $lcPath = str_replace('{locale}', DEFAULT_LANGUAGE, $filePath);

            if(file_exists($lcPath)){
                return file_get_contents($lcPath);
            }
        }

        $filePath = str_replace('/{locale}', '', $filePath);

        if(!file_exists($filePath)){
            return '';
        }

        return file_get_contents($filePath);
    }

    public static function setupDefaults(array $data, array $defaults = []): array
    {
        foreach($defaults as $k => $v){
            if(array_key_exists($k, $defaults)){
                $data[$k] = $data[$k]?? $v;
            }
        }
        
        return $data;
    }
}
?>