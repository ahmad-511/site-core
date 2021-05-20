<?php
declare (strict_types = 1);
namespace App\Core;

use App\Core\Guard;
use App\Core\Request;
use App\Core\Response;
use App\Core\Result;
use App\Core\Route;

class Router{
    private static string $HomePageCode = 'home';
    private static array $Locales = ['en'];
    private static string $DefaultLocale = 'en';
    private static array $LocaleMapper = [];
    private static string $ViewsDir = __DIR__ .'/../views/';
    private static string $AccessDeniedView = 'access-denied';
    private static string $PageNotFoundView = 'page-not-found';
    private static string $RedirectViewSession = 'redirect_view';
    
    private static string $CurrentLayout = 'main';
    private static array $Routes = [];

    private static string $CurrentViewCode = '';
    private static string $CurrentLocaleCode = '';

    /**
     * Set home page code
     * @param string $homePageCode Home page code used to detect whether or not current page is the home page so we can omit the code from the url
     * @return void
     */
    public static function setHomePageCode(string $homePageCode){
        self::$HomePageCode = $homePageCode;
    }

    /**
     * Set accepted locale codes
     * @param array $localeCodes Array of accepted language locale codes the router can use to detect the language if one of these codes present in the request url's first sigment
     * @return void
     */
    public static function setLocales(array $localeCodes){
        self::$Locales = $localeCodes;
    }
    
    /**
     * Get accepted locale codes
     * @return array of accepted locale codes
     */
    public static function getLocales(){
        return self::$Locales;
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
     * Define a specific locale codes for specific views, this will overwrite locale code passed in the request to force rendering views in one language 
     * @param Array $localMapper Associative array of [viewCode => localeCode]
     * @return void
     */
    public static function setLocaleMapper(array $localMapper = []){
        self::$LocaleMapper = $localMapper;
    }

    /**
     * Set views directory path
     * @param string $viewsDir The path on the disk where views files are stored
     * @return void
     */
    public static function setViewsDir(string $viewsDir = ''){
        self::$ViewsDir = $viewsDir;
    }

    /**
     * Set access denied view code
     * @param string $accessDeniedView
     * @return void
     */
    public static function setAccessDeniedView(string $accessDeniedView = ''){
        self::$AccessDeniedView = $accessDeniedView;
    }

    /**
     * Set page not found view code
     * @param string $pageNotFoundView
     * @return void
     */
    public static function setPageNotFoundView(string $pageNotFoundView = ''){
        self::$PageNotFoundView = $pageNotFoundView;
    }

    /**
     * Set session variable name used to store last visited view if redirection is triggered (i.e. by Guard or manually) 
     * @param string $redirectViewSession
     * @return void
     */
    public static function setRedirectViewSession(string $redirectViewSession = ''){
        self::$RedirectViewSession = $redirectViewSession;
    }

    /**
     * Get current loaded view code
     * @return string View code
     */
    public static function getCurrentViewCode(){
        return self::$CurrentViewCode;
    }

    /**
     * Get current locale code
     * @return string Locale code
     */
    public static function getCurrentLocaleCode(){
        return self::$CurrentLocaleCode;
    }

    /**
     * Add custom GET route
     * @param string $path Route path (/home, /about, /something/something-else/whatever,...)
     * @param string|function|array $controller If it's a string it's a view code, if it's a function it must return the view contents, if it's an array as [class, method] it can return Result or anything
     * @param string $name Route name (optional), used to build a route path from itss name
    */
    public static function get($path, $controller, $name = ''){
        $route = new Route('get', $path, $controller, $name);
        self::$Routes[] = $route;
    }

    /** Add custom POST route
     * @param string $path Route path (/home, /about, /something/something-else/whatever,...)
     * @param string|function|array $controller If it's a string it's a view code, if it's a function it must return the view contents, if it's an array as [class, method] it can return Result or anything
     * @param string $name Route name (optional), used to build a route path from its name
    */
    public static function post($path, $controller, $name = ''){
        $route = new Route('post', $path, $controller, $name);
        self::$Routes[] = $route;
    }

    /** Add custom route regardless of the method
     * @param string $path Route path (/home, /about, /something/something-else/whatever,...)
     * @param string|function|array $controller If it's a string it's a view code, if it's a function it must return the view contents, if it's an array as [class, method] it can return Result or anything
     * @param string $name Route name (optional), used to build a route path from its name
    */
    public static function any($path, $controller, $name = ''){
        $route = new Route('any', $path, $controller, $name);
        self::$Routes[] = $route;
    }

    /** Get locale code for current request or for specified view code
     * @param string $viewCode View code (optional) to get the locale code for it
     * @return string Locale code
    */ 
    public static function getLocaleCode($viewCode = ''){
        // Try to get locale code from Locale Mapper
        if(!empty($viewCode) && array_key_exists($viewCode, self::$LocaleMapper)){
            return self::$LocaleMapper[$viewCode];
        }

        // Get locale code using the first sigment from the request url
        $localeCode = strtolower(Request::getURISegments(false)[0]??'');
        
        // Make sure that found locale code is accepted
        if(!in_array($localeCode, self::$Locales)){
            $localeCode = '';
        }

        // Use defaule router's locale
        if(empty($localeCode)){
            $localeCode = self::$DefaultLocale;
        }

        return $localeCode;
    }

    /**
     * Get view full url combined with locale code
     * @param string $routeName Route name
     * @param array $params Route parameters
     * @param string $localeCode Locale code
     * @return string Localized url for the view with view code is words uppercased
    */
    public static function routeUrl($routeName, $params = [], $localeCode = ''){
        if(empty($localeCode)){
            $localeCode = strtolower(self::getLocaleCode($routeName));
        }

        if($localeCode == self::$DefaultLocale){
            $localeCode = '';
        }

        if(!empty($localeCode)){
            $localeCode .= '/';
        }

        // Check for custom route
        $path = null;

        foreach(self::$Routes as $route){
            if($routeName == $route->name){
                $path = $route->path;

                break;
            }
        }

        if(!empty($path)){
            // Replace route params
            foreach($params as $n => $v){
                $path = str_replace('{'.$n.'}', $v, $path);
            }
        }elseif(is_null($path)){ // Use route name as path for auto routes
            $path = ucwords($routeName);

            // Add route params
            if(is_array($params) && !empty($params)){
                $path .= '/' . implode('/', array_values($params));
            }
        }

        return  strtoupper($localeCode) . $path;
    }

    /**
     * Get the view file path on the disk considering localized version
     * @param string $viewCode View code to get its path
     * @param string $localeCode Language locale code
     * @return string Path on disk for localized version of the view if exists, fallback to original file location
     * 
    */
    public static function getViewPath($viewCode = '', $localeCode = '')
    {
        $viewPath = self::$ViewsDir .$localeCode. $viewCode . '.php';

        // Get default page if locale version doesn't exist
        if (!file_exists($viewPath)) {
            $viewPath = self::$ViewsDir . $viewCode . '.php';
        }
    
        // Check if file path exists, if not return Page not found
        if (!file_exists($viewPath)) {
            return false;
        }
        
        return $viewPath;
    }

    public static function getPageNothFoundPath($localeCode = ''){
        // Use localized version of the page not found view
        $path = self::$ViewsDir .$localeCode .self::$PageNotFoundView . '.php';
            
        // Use original page not found path
        if (!file_exists($path)) {
            $path = self::$ViewsDir .self::$PageNotFoundView . '.php';
        }

        return $path;
    }

    /** Set new layout file to be used when rendering a view */
    public static function setLayout($layout){
        self::$CurrentLayout = $layout;
    }

    /** Routing starting point, called once in the index.php entry file */
    public static function resolve(){
        // Check user defined routes
        $reqMethod = strtolower(Request::getMethod());
        $reqPath = Request::getPath();

        // Try to find custom route
        if(!self::customRouting($reqMethod, $reqPath)){
            // Try auto routing procedure
            self::autoRouting();
        }
    }

    private static function customRouting($reqMethod, $reqPath){
        $controller = '';
        foreach(self::$Routes as $route){
            if($route->method !='any' && $reqMethod != $route->method){
                continue;
            }

            $quotedPath = preg_quote($route->path);
            $pattern = '#^' . preg_replace('/\\\{.+?\\\}/', '(.+?)', $quotedPath, -1, $paramsCount) . '$#';

            $params = [];

            // Find matched route
            if(preg_match($pattern, $reqPath, $pValues) === 1){
                $controller = $route->controller;

                // Extract route params
                if($paramsCount > 0){
                    // Extract path params names
                    $pattern = str_replace('(.+?)', '{(.+?)}', $pattern);

                    preg_match($pattern, $route->path, $pNames);

                    // Remove first element which contains the full string
                    array_shift($pValues);
                    array_shift($pNames);

                    // Assign param names to param values
                    $params = array_combine($pNames, $pValues);
                }
                
                break;
            }
        }

        if(!empty($controller)){
            // When controller is a string it represents the view code
            if(is_string($controller)){
                self::loadView($controller, $params);

                return true;
            }elseif(is_array($controller) && is_callable($controller)){
                self::executeController($controller[0], $controller[1], $params);
                
                return true;
            }elseif(is_callable($controller)){
                // When controller is a function the view content must be returned by it
                $viewContent = $controller($params);
                self::loadLayout($viewContent);
                
                return true;
            }
        }

        return false;
    }

    // When no custom routing is specified for current request, fallback to auto routing procedure
    private static function autoRouting(){
        $viewCode = self::getViewCode();

        // Use $URISegments as method params for both views and controllers
        $URISegments = Request::getURISegments();

        // All API calls
        if ($viewCode == 'api') {
            $controller = $URISegments[1] ?? '';
            $method = $URISegments[2] ?? '';

            $controller = '\\App\\Controller\\'.$controller.'Controller';
            // Execlude controller and method names from the params list
            self::executeController($controller, $method, array_slice($URISegments, 3));

            return;
        }else{
            self::loadView($viewCode, $URISegments);

            return;
        }
    }

    /** 
     * Get view code from request url using first sigment (ingonring locale sigment)
     * @return string View code from the first uri segment if it has one or more sigment (excluding locale code sigment), home page code otherwise
    */
    private static function getViewCode()
    {
        $segments = Request::getURISegments();
        $viewCode = strtolower($segments[0]??'');
        
        // Use home page view code if nothing is specified
        if (empty($viewCode)) {
            $viewCode = self::$HomePageCode;
        }

        return $viewCode;
    }

    /** Execute controller method */
    public static function executeController($controller, $method, $params = []){
        if (!self::controllerExecutionGuard($controller, $method)) {
            $result = new Result(null, 'Method call not allowed', 'error');
            Response::send($result, 403);
        }

        if (class_exists($controller)) {
            // Instantiate controller with POST params
            $obj = new $controller(Request::body());
        } else {
            $result = new Result(null, "Controller $controller doesn't exist", 'error');
            Response::send($result, 404);
        }

        if (!method_exists($obj, $method)) {
            $result = new Result(null, "Method $controller::$method doesn't exist", 'error');
            Response::send($result, 404);
        }

        // Call controller's method and pass all URI segments as params
        try {
            $result = $obj->$method($params);
            // Set server message / Redirect 
            if ($result instanceof Result) {
                Response::send($result);
            } else {
                echo $result;
            }

        } catch (\Exception $ex) {
            $result = new Result(null, $ex->getMessage(), 'exception');
            Response::send($result, 500);
        }
    }

    /** Load view content with currently used layout */
    public static function loadView($viewCode, $requestParams, $localeCode = null){
        // Get view's locale code if not passed
        if(empty($localeCode)){
            $localeCode = self::getLocaleCode($viewCode);
        }

        $viewCode = self::viewAccessGuard($viewCode);

        $viewPath = self::getViewPath($viewCode, $localeCode);

        self::$CurrentViewCode = $viewCode;
        self::$CurrentLocaleCode = $localeCode;

        // If view path not exists, switch to page not found view and set 404 response code
        if($viewPath === false){
            $viewPath = self::getPageNothFoundPath($localeCode);

            // Set page not found response status
            Response::setStatus(404);
        }

        $viewContent = '';

        if (!empty($viewPath)) {
            ob_start();
            include $viewPath;
            $viewContent = ob_get_clean();    
        }

        // Load layout file template
        self::loadLayout($viewContent);
    }

    /** Load current layout content
     *  echo $viewContent inside it as a place holder for the current view
     */
    public static function loadLayout($viewContent = ''){
        $layout = (self::$CurrentLayout?? 'main') . '.php';

        include self::$ViewsDir . 'layouts'. DIRECTORY_SEPARATOR . $layout ;
    }

    /**
     * Check whether or not the current user is allowed to access specified view
     * @param string $viewCode View code to check against current user permissions
     * @return $viewCode if access allowed, redirectCode/accessDeniedView code otherwise
     */
    public static function viewAccessGuard($viewCode){
        $res = Guard::canAccess($viewCode);
        $canAccess = $res->canAccess ?? false;
        $redirectCode = $res->redirectCode ?? '';

        // Save current path for later use when redirect code is present
        if(!empty($redirectCode)){
            $_SESSION[self::$RedirectViewSession] = $_SERVER['REQUEST_URI']??'';
            $_SERVER['REQUEST_URI'] = $redirectCode;
        }

        // If access allowed for current user then load specified view
        if ($canAccess) {
            return $viewCode;
        }
        
        // Return redirect view code if presents
        if (!empty($redirectCode)) {
            return $redirectCode;
        } else {
            // Set 403 forbidden response status and return access deinied view code
            Response::setStatus(403);
            return self::$AccessDeniedView;
        }
    }

    /** 
     * Check whether or not the current user is allowed to execute specified controller method
     * @param string $controller Controller name
     * @param string $method Controller's method name
     * @return bool true if allowed, false otherwise
    */
    public static function controllerExecutionGuard($controller, $method){
        return Guard::methodAllowed($controller, $method);
    }

    /**
     * Get last saved redirect view code
     * @return string Redirect code
    */
    public static function getRedirectViewCode()
    {
        $redirect = $_SESSION[self::$RedirectViewSession] ?? '';
        
        if (!empty($redirect)) {
            unset($_SESSION[self::$RedirectViewSession]);
        }

        return $redirect;
    }
}
?>