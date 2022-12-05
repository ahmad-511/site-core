<?php
declare (strict_types = 1);
namespace App\Core;

use App\Core\Guard;
use App\Core\Request;
use App\Core\Response;
use App\Core\Result;
use App\Core\Route;
use App\Core\GuradResult;

class Router{
    private static bool $AutoRouting = true;
    private static string $HomePageCode = 'home';
    private static array $Locales = ['en'];
    private static string $DefaultLocale = 'en';
    private static array $LocaleMapper = [];
    private static string $ViewsDir = __DIR__ .'/../views/';
    private static string $AccessDeniedView = 'access-denied';
    private static string $PageNotFoundView = 'page-not-found';
    private static string $RedirectViewSession = 'redirect_view';
    private static string $NullParamValue = '.';
    
    private static bool $CaseSensitivity = true;
    private static string $CurrentLayout = 'main';
    private static array $Routes = [];

    private static string $CurrentRouteName = '';
    private static string $CurrentLocaleCode = '';
    private static string $CurrentFileName = '';

    private static $ViewContent = '';

    /**
     * Enabled/Disable auto routing 
     * @param bool $autoRouting when true Router will try to guess the correct route (custom routes take precedence)
     * @return void
     */
    public static function setAutoRouting(bool $autoRouting = true){
        self::$AutoRouting = $autoRouting;
    }

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
     * @param Array $localMapper Associative array of [route name => locale code]
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
     * Set the value that will be used if passed param value is null or empty string (Used when generating routeUrl)
     * @param string $NullParamValue
     * @return void
     */
    public static function setNullParamValue(string $NullParamValue = '.'){
        self::$NullParamValue = $NullParamValue;
    }

    /**
     * Set custom route's case sensitivity checking mode
     * @param bool $isCaseSensitive true: case sensitive, false: case insensitive
     * @return void
     */
    public static function setCaseSensitivity(bool $isCaseSensitive = true){
        self::$CaseSensitivity = $isCaseSensitive;
    }

    /**
     * Get current loaded view code
     * @return string View code
     */
    public static function getCurrentRouteName(){
        return self::$CurrentRouteName;
    }

    /**
     * Get current loaded view file name
     * @return string file name
     */
    public static function getCurrentFileName(bool $excludeDir = false){
        if($excludeDir){
            return basename(self::$CurrentFileName);
        }
        
        return self::$CurrentFileName;
    }

    /**
     * Get current locale code
     * @return string Locale code
     */
    public static function getCurrentLocaleCode(){
        return strtolower(self::$CurrentLocaleCode);
    }

    /**
     * Get current view content
     * @return string View content
     */
    public static function getViewContent(){
        return self::$ViewContent;
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

    /** Get locale code for current request or specified route name
     * @param string $routeName View route name or file name (optional) to get the locale code for it
     * @return string Locale code
    */ 
    public static function getLocaleCode($routeName = ''){
        // Try to get locale code from Locale Mapper
        if(!empty($routeName) && array_key_exists($routeName, self::$LocaleMapper)){
            return self::$LocaleMapper[$routeName];
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
     * @param boolean $omitDefaultLocale Don't include locale code for default route locale
     * @return string Localized url for the view with view code words uppercased
    */
    public static function routeUrl($routeName, $params = [], $localeCode = '', $omitDefaultLocale = true){
        if(empty($localeCode)){
            $localeCode = strtolower(self::getLocaleCode($routeName));
        }

        if($omitDefaultLocale && $localeCode == self::$DefaultLocale){
            $localeCode = '';
        }

        if(!empty($localeCode)){
            $localeCode = '/' . $localeCode ;
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
            if(!empty($params)){
                // Store a copy of original route params to be used in replacing un-named params (values passed as sequencial array)
                preg_match_all("#\{.+?\}#", $path, $origParams);
                $origParams = $origParams[0];

                // Replace named params
                foreach($params as $n => $v){
                    if(is_null($v) || $v == ''){
                        $v = self::$NullParamValue;
                    }

                    $path = str_replace(['{'.$n.'?}', '{'.$n.'}'], [$v, $v], $path);
                }

                // Replace un-named params
                // Get only numerically keyed values
                $params = array_filter($params, function($key){
                    return is_numeric($key);
                }, ARRAY_FILTER_USE_KEY);

                // Replace sequencial params with respect to original params order
                $path = preg_replace_callback("#\{.+?\}#", function($m) use($params, $origParams) {
                    $pos = array_search($m[0], $origParams);

                    if($pos !== false && array_key_exists($pos, $params)){
                        $v = $params[$pos]??'';
                        if(is_null($v) || $v == ''){
                            $v = self::$NullParamValue;
                        }
                        
                        return $v;
                    }
                   
                    return $m[0];
                }, $path);

                // Clean up optional params
                $path = preg_replace("#\{.+?\?}#", ".", $path);
            }
        }elseif(is_null($path)){ // Use route name as path for auto routes
            $path = ucwords($routeName);

            // Add route params
            if(is_array($params) && !empty($params)){
                $path .= '/' . implode('/', array_values($params));
            }
        }

        if(substr($path, 0, 1) != '/'){
            $path = '/' . $path;
        }

        return  strtoupper($localeCode) . $path;
    }

    /**
     * Get the view file path on the disk considering localized version
     * @param string $fileName View file name to get its path
     * @param string $localeCode Language locale code
     * @return string Path on disk for localized version of the view if exists, fallback to original file location
     * 
    */
    public static function getViewPath($fileName = '', $localeCode = '')
    {
        if(empty($localeCode)){
            $localeCode = self::$DefaultLocale;
        }

        if(!empty($localeCode)){
            $localeCode .= DIRECTORY_SEPARATOR;
        }

        $viewPath = self::$ViewsDir .$localeCode. $fileName . '.php';

        if (file_exists($viewPath)) {
            return $viewPath;
        }
        
        // Get the page from the default locale folder if locale version doesn't exist
        $viewPath = self::$ViewsDir . self::$DefaultLocale . DIRECTORY_SEPARATOR . $fileName . '.php';

        if (file_exists($viewPath)) {
            return $viewPath;
        }
        
        // Get the page from view root if default locale version doesn't exist
        $viewPath = self::$ViewsDir . $fileName . '.php';
    
        if (file_exists($viewPath)) {
            return $viewPath;
        }
        
        // Return false (Page not found)
        return false;
    }

    public static function getPageNothFoundPath($localeCode = ''){
        // Use localized version of the page not found view
        $path = self::$ViewsDir .$localeCode . DIRECTORY_SEPARATOR .self::$PageNotFoundView . '.php';
            
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
        $langCode = Request::getLocaleCode();
        self::$CurrentLocaleCode = empty($langCode)?self::$DefaultLocale: $langCode;

        // Try to find custom route
        if(!self::customRouting()){
            // Try auto routing procedure
            if(self::$AutoRouting){
                self::autoRouting();
            }else{
                // Send only 404 response when requested path pointing to inexisting file
                Response::setStatus(404);
                self::renderView(self::$PageNotFoundView, []);
            }
        }
    }

    private static function customRouting(){
        $reqMethod = strtolower(Request::getMethod());
        $reqPath = Request::getPath();

        $controller = '';

        foreach(self::$Routes as $route){
            if($route->method !='any' && $reqMethod != $route->method){
                continue;
            }

            $quotedPath = preg_quote($route->path);
            $pattern = '#^' . preg_replace(['/\\/\\\{.+?\\?\\\}/', '/\\\{.+?\\\}/'], ['(?:/(.+?))?', '(.+?)'], $quotedPath, -1, $paramsCount) . '$#';

            if(!self::$CaseSensitivity){
                $pattern .= 'i';
            }

            $params = [];

            // Find matched route (ignoring locale code)
            if(preg_match($pattern, $reqPath, $pValues) === 1){
                $controller = $route->controller;
                self::$CurrentRouteName =  $route->name;
                
                // Extract route params
                if($paramsCount > 0){
                    // Extract path params names
                    $pattern = strtr($pattern,[
                        '(?:/(.+?))?' => '/{(.+?)\?}',
                        '(.+?)' => '{(.+?)}'
                    ]);

                    preg_match($pattern, $route->path, $pNames);

                    // Remove first element which contains the full string
                    array_shift($pValues);
                    array_shift($pNames);

                    // Add optional params values
                    if(count($pValues) < count($pNames)){
                        $pValues = array_pad($pValues, count($pNames), null);
                    }

                    // Assign param names to param values
                    $params = array_combine($pNames, $pValues);
                }
                
                break;
            }
        }

        if(!empty($controller)){
            // When controller is a string it represents the view code
            if(is_string($controller)){
                self::renderView($controller, $params);
                
                return true;
            }elseif(is_array($controller) && is_callable($controller, true)){
                self::executeController($controller[0], $controller[1], $params);
                
                return true;
            }elseif(is_callable($controller)){
                // When controller is a function the view content must be returned by it
                $viewContent = $controller($params);
                self::renderLayout($viewContent);
                
                return true;
            }
        }

        return false;
    }

    // When no custom routing is specified for current request, fallback to auto routing procedure
    private static function autoRouting(){
        $routeName = self::getAutoRouteName();
        self::$CurrentRouteName = $routeName;

        // Use $URISegments as method params for both views and controllers
        $URISegments = Request::getURISegments();

        // All API calls
        if ($routeName == 'api') {
            $controller = $URISegments[1] ?? '';
            $method = $URISegments[2] ?? '';

            $controller = '\\App\\Controller\\'.$controller.'Controller';
            self::$CurrentRouteName = $controller.'\\'.$method;

            // Execlude controller and method names from the params list
            self::executeController($controller, $method, array_slice($URISegments, 3));

            return;
        }else{
            self::renderView($routeName, $URISegments);

            return;
        }
    }

    /** 
     * Get route name from request url using first sigment (ingonring locale sigment)
     * @return string Route name from the first uri segment if it has one or more sigment (excluding locale code sigment), home page code otherwise
    */
    private static function getAutoRouteName()
    {
        $segments = Request::getURISegments();
        $routeName = strtolower($segments[0]??'');
        
        // Use home page view code if nothing is specified
        if (empty($routeName)) {
            $routeName = self::$HomePageCode;
        }

        return $routeName;
    }

    /** Execute controller method */
    public static function executeController($controller, $method, $params = []){
        $guardResult = self::controllerExecutionGuard($controller, $method);


        if (!$guardResult->isAllowed) {
            $result = new Result(null, $guardResult->message?? "You do not have necessary privileges to call $controller::$method", 'error', $guardResult->redirectFileName);
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
            // If result is null, the view is rendered from controller, ex: Router::renderView('view name')
            if(is_null($result)){
                return;
            }

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
    public static function renderView($fileName, $params = [], $localeCode = null){
        if(empty(self::$CurrentRouteName)){
            self::$CurrentRouteName = $fileName;

            $isViewSuffix = substr(self::$CurrentRouteName, -strlen('-view'));
            if($isViewSuffix != '-view'){
                self::$CurrentRouteName .= '-view';
            }
        }

        if(empty($localeCode)){
            $localeCode = self::getCurrentLocaleCode();
        }

        $viewContent = self::loadViewContent($fileName, $params, $localeCode);
        // Load layout file template
        self::renderLayout($viewContent);
    }

    /** Load view content */
    private static function loadViewContent(string $fileName, array $params = [], $localeCode = null){
        // If CurrentRouteName is not set by customRouting function then use the automatic one
        if(empty(self::$CurrentRouteName)){
            self::$CurrentRouteName = $fileName;

            $isViewSuffix = substr(self::$CurrentRouteName, -strlen('-view'));
            if($isViewSuffix != '-view'){
                self::$CurrentRouteName .= '-view';
            }
        }
        
        [$redirectFileName, $guardMessage] = self::viewAccessGuard(self::$CurrentRouteName);
        
        if(!empty($redirectFileName)){
            $fileName = $redirectFileName;
        }

        self::$CurrentFileName = $fileName;

        // Get view's locale code if not passed
        if(empty($localeCode)){
            $localeCode = self::getLocaleCode($fileName);
        }

        self::$CurrentLocaleCode = $localeCode;

        // Add guard message (if any) in params
        $params = $params??[];
        $params['GUARD_MESSAGE'] = $guardMessage;

        return self::renderContent($fileName, $params, $localeCode, true);
    }

    /** Render or return localized version of the specified file */
    public static function renderContent(string $fileName, array $params = [], $localeCode = null, bool $return = false): string
    {
        // Get view's locale code if not passed
        if(empty($localeCode)){
            $localeCode = self::getLocaleCode($fileName);
        }

        $viewPath = self::getViewPath($fileName, $localeCode);

        // If view path not exists, switch to page not found view and set 404 response code
        if($viewPath === false){
            $viewPath = self::getPageNothFoundPath($localeCode);
            self::$CurrentFileName = self::$PageNotFoundView;
            // Set page not found response status
            Response::setStatus(404);
        }

        $viewContent = '';

        if (!empty($viewPath)) {
            ob_start();
            include $viewPath;
            $viewContent = ob_get_clean();
        }

        if($return){
            return $viewContent;
        }

        echo $viewContent;

        return '';
    }

    /** Load current layout content
     *  echo $viewContent inside it as a place holder for the current view
     */
    public static function renderLayout($viewContent = ''){
        self::$ViewContent = $viewContent;

        $layout = (self::$CurrentLayout?? 'main') . '.php';

        include self::$ViewsDir . 'layouts'. DIRECTORY_SEPARATOR . $layout ;
    }

    /**
     * Check whether or not the current user is allowed to access specified view
     * @param string $routeName View code to check against current user permissions
     * @return array [$routeName, $guardMessage], if access is allowed $routeName is empty string, otherwise it's redirectFileName/accessDeniedView
     */
    public static function viewAccessGuard($routeName){
        $guardResult = Guard::canView($routeName);
        $isAllowed = $guardResult->isAllowed ?? false;
        $redirectFileName = $guardResult->redirectFileName ?? '';
        $guardMessage = $guardResult->message ?? '';

        // Clear old saved url that requires login when navigating to another public url
        if(empty($redirectFileName)){
            if(!empty($_SESSION[self::$RedirectViewSession])){
                unset($_SESSION[self::$RedirectViewSession]);
            }
        }else{
            // Save current url for later use when redirect code is present
            $_SESSION[self::$RedirectViewSession] = Request::getURI();
        }

        // If access allowed for current user then load specified view
        if ($isAllowed) {
            return ['', $guardMessage];
        }
        
        // Return redirect view code if presents
        if (!empty($redirectFileName)) {
            return [$redirectFileName, $guardMessage];
        } else {
            // Set 403 forbidden response status and return access deinied view code
            Response::setStatus(403);
            return [self::$AccessDeniedView, $guardMessage];
        }
    }

    /** 
     * Check whether or not the current user is allowed to execute specified controller method
     * @param string $controller Controller name
     * @param string $method Controller's method name
     * @return GuradResult true if allowed, false otherwise
    */
    public static function controllerExecutionGuard($controller, $method):GuradResult{
        return Guard::canExecute($controller, $method, self::$CurrentRouteName);
    }

    /**
     * Get last saved redirect view code
     * @return string Redirect code
    */
    public static function getRedirectRouteName()
    {
        $redirect = $_SESSION[self::$RedirectViewSession]?? '';
        
        if (!empty($redirect)) {
            unset($_SESSION[self::$RedirectViewSession]);
        }

        return $redirect;
    }
}
?>