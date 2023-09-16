<?php
declare (strict_types = 1);
namespace App\Core;

use App\Core\Guard;
use App\Core\Request;
use App\Core\Response;
use App\Core\Result;
use App\Core\Route;
use App\Core\GuardResult;

class Router{
    private static bool $AutoRouting = true;
    private static string $HomePageCode = 'home';
    private static array $Locales = ['en'];
    private static string $DefaultLocale = 'en';
    private static array $LocaleMapper = [];
    private static string $ViewsDir = __DIR__ .'/../views/';
    private static string $ErrorView = 'error';
    private static string $RedirectViewSession = 'redirect_view';
    private static string $NullParamValue = '.';
    
    private static bool $CaseSensitivity = true;
    private static string $DefaultLayout = 'main';
    private static array $Routes = [];

    private static string $CurrentRouteName = '';
    private static string $CurrentLocaleCode = '';
    private static string $CurrentFileName = '';
    private static ?string $CurrentLayout = null;

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
     * @param array $localeCodes Array of accepted language locale codes the router can use to detect the language if one of these codes present in the request url's first segment
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
     * Set error view name
     * @param string $errorView error view file name
     * @return void
     */
    public static function setErrorView(string $errorView = ''){
        self::$ErrorView = $errorView;
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
     * Set the value that will be used if passed param value is null or empty string (Used when generating route url)
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
     * Set current layout
     * @param string $layout file to be used in current view rendering
     * @return void
     */
    public static function setCurrentLayout(string $currentLayout){
        self::$CurrentLayout = $currentLayout;
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
     * Get current layout name
     * @return string Layout file name (without extension)
     */
    public static function getCurrentLayout(){
        return self::$CurrentLayout;
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
     * @param string $name Route name (optional), used to build a route path from its name
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

    /** Add custom PUT route
     * @param string $path Route path (/home, /about, /something/something-else/whatever,...)
     * @param string|function|array $controller If it's a string it's a view code, if it's a function it must return the view contents, if it's an array as [class, method] it can return Result or anything
     * @param string $name Route name (optional), used to build a route path from its name
    */
    public static function put($path, $controller, $name = ''){
        $route = new Route('put', $path, $controller, $name);
        self::$Routes[] = $route;
    }

    /** Add custom PATCH route
     * @param string $path Route path (/home, /about, /something/something-else/whatever,...)
     * @param string|function|array $controller If it's a string it's a view code, if it's a function it must return the view contents, if it's an array as [class, method] it can return Result or anything
     * @param string $name Route name (optional), used to build a route path from its name
    */
    public static function patch($path, $controller, $name = ''){
        $route = new Route('patch', $path, $controller, $name);
        self::$Routes[] = $route;
    }

    /** Add custom DELETE route
     * @param string $path Route path (/home, /about, /something/something-else/whatever,...)
     * @param string|function|array $controller If it's a string it's a view code, if it's a function it must return the view contents, if it's an array as [class, method] it can return Result or anything
     * @param string $name Route name (optional), used to build a route path from its name
    */
    public static function delete($path, $controller, $name = ''){
        $route = new Route('delete', $path, $controller, $name);
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

        // Get locale code using the first segment from the request url
        $localeCode = strtolower(Request::getURLSegments(false)[0]??'');
        
        // Make sure that found locale code is accepted
        if(!in_array($localeCode, self::$Locales)){
            $localeCode = '';
        }

        // Use default router's locale
        if(empty($localeCode)){
            $localeCode = self::$DefaultLocale;
        }

        return $localeCode;
    }

    /**
     * Get full route url combined with locale code
     * @param string $routeName Route name
     * @param array $params Route parameters
     * @param string $localeCode Locale code
     * @param boolean $omitDefaultLocale Don't include locale code for default route locale
     * @return string Localized url for the view with view code words uppercased
    */
    public static function route($routeName, $params = [], $localeCode = '', $omitDefaultLocale = true){
        if(is_null($params )){
            $params = [];
        }

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
            // Store a copy of original route params to be used in replacing un-named params (values passed as sequential array)
            preg_match_all("#\{.+?\}#", $path, $origParams);
            $origParams = $origParams[0];
            
            if(!empty($origParams)){
                //$params = array_pad($params, count($origParams), self::$NullParamValue);

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

                // Replace sequential params with respect to original params order
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
                $path = preg_replace("#\{([^{]+?)\?\}#", self::$NullParamValue, $path);
            }
        }elseif(is_null($path)){ // Use route name as path for auto routes
            $path = ucwords($routeName);

            // Add route params
            if(is_array($params) && !empty($params)){
                $path .= '/' . implode('/', array_values($params));
            }
        }

        // Clean up path ending null params
        $path = preg_replace('#(' . preg_quote('/' . self::$NullParamValue) . ')+$#', '', $path);

        if(!str_starts_with($path, '/')){
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

    public static function getErrorViewPath($localeCode = ''){
        // Use localized version of the error view
        $path = self::$ViewsDir .$localeCode . DIRECTORY_SEPARATOR .self::$ErrorView . '.php';
            
        // Use original error view path
        if (!file_exists($path)) {
            $path = self::$ViewsDir .self::$ErrorView . '.php';
        }

        return $path;
    }

    /** Set new layout file to be used when rendering a view */
    public static function setDefaultLayout($layout){
        self::$DefaultLayout = $layout;
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
                // Send only 404 response when requested path is not in custom routing list nor can be automatically determined using the file structure
                self::sendError(404, 'Undefined route');
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
            $pattern = '#^' . preg_replace(['/\\/\\\{[^{]+?\\?\\\}/', '/\\\{[^{]+?\\?\\\}/', '/\\\{.+?[^?]\\\}/'], ['(?:/([^{]+?))?', '(?:([^{]+?))?', '([^{]+?)'], $quotedPath, -1, $paramsCount) . '$#';

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
                        '(?:/([^{]+?))?' => '/\{([^{]+?)\?\}',
                        '(?:([^{]+?))?' => '\{([^{]+?)\?\}',
                        '([^{]+?)' => '\{([^{]+?)\}'
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
                $result = new View($controller, $params, '', '', 200, ['Content-Type: text/html']);
                self::sendResponse($result);
                
                return true;
            }elseif(is_array($controller) && is_callable($controller, true)){
                self::executeController($controller[0], $controller[1], $params);
                
                return true;
            }elseif($controller instanceof \Closure){
                $result = $controller($params);
                self::sendResponse($result);
                
                return true;
            }
        }

        return false;
    }

    // When no custom routing is specified for current request, fallback to auto routing procedure
    private static function autoRouting(){
        $routeName = self::getAutoRouteName();
        self::$CurrentRouteName = $routeName;

        // Use $URLSegments as method params for both views and controllers
        $URLSegments = Request::getURLSegments();

        // All API calls
        if ($routeName == 'api') {
            $controller = $URLSegments[1] ?? '';
            $method = $URLSegments[2] ?? '';

            $controller = '\\App\\Controller\\'.$controller.'Controller';
            self::$CurrentRouteName = $controller.'\\'.$method;

            // Exclude controller and method names from the params list
            self::executeController($controller, $method, array_slice($URLSegments, 3));

            return;
        }else{
            self::sendResponse(new View($routeName, $URLSegments, '', '', 200, ['Content-Type: text/html']));
            return;
        }
    }

    /** 
     * Get route name from request url using first segment (ignoring locale segment)
     * @return string Route name from the first url segment if it has one or more segment (excluding locale code segment), home page code otherwise
    */
    private static function getAutoRouteName()
    {
        $segments = Request::getURLSegments();
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
            self::sendError($guardResult->statusCode, $guardResult->message?? "You do not have necessary privileges to call $controller::$method", $guardResult->redirectViewCode);
        }

        if (class_exists($controller)) {
            // Instantiate controller with POST params
            $obj = new $controller(Request::body());
        } else {
            self::sendError(404, "Controller $controller doesn't exist");
        }

        if (!method_exists($obj, $method)) {
            self::sendError(404, "Method $controller::$method doesn't exist");
        }

        // Call controller's method and pass all URL segments as params
        try {
            $result = $obj->$method($params);
            self::sendResponse($result);

        } catch (\Exception $ex) {
            self::sendError(500, $ex->getMessage());
        }
    }

    private static function sendError($statusCode, $message = '', $redirect = ''){
        if(Request::accept('application/json')){
            $result = new Result(null, $message, 'error', $redirect);
            self::sendResponse($result, $statusCode, ['application/json']);
            return;
        }

        $view = new View('error', [
                "statusCode" => $statusCode,
                "errorMessage" => $message,
                "redirect" => $redirect
            ], '', '' ,$statusCode, ['Content-Type: text/html']);
        self::sendResponse($view);
    }
    
    private static function sendResponse($result, int $statusCode = 200, array $headers = []){
        if($result instanceof Response){ 
            Response::send($result);

        }elseif($result instanceof View){
            self::$CurrentLayout = $result->layout;

            // Render the view
            $viewContent = self::renderView($result->fileName, $result->params, $result->localeCode);

            // View layout can be changed from within currently rendered view by calling Router::setCurrentLayout()
            if(self::$CurrentLayout != $result->layout){
                $result->layout = self::$CurrentLayout;
            }
            
            // Merge view content with specified layout file template
            $viewContent = self::renderLayout($viewContent, $result->layout);
            Response::sendRaw($viewContent, $result->statusCode, $result->headers);
            return;

        }elseif ($result instanceof Result) {
            Response::json($result, $statusCode, $headers);
            return;
        }
        
        Response::sendRaw($result, $statusCode, $headers);
        return;
    }

    /** Load view content with currently used layout */
    public static function renderView(string $fileName, array $params = [], string $localeCode = null){
        if(empty(self::$CurrentRouteName)){
            self::$CurrentRouteName = $fileName;

            if(!str_ends_with(self::$CurrentRouteName, '-view')){
                self::$CurrentRouteName .= '-view';
            }
        }

        if(empty($localeCode)){
            $localeCode = self::getCurrentLocaleCode();
        }

        return self::loadViewContent($fileName, $params, $localeCode);
    }

    /** Load view content */
    private static function loadViewContent(string $fileName, array $params = [], $localeCode = null){
        // If CurrentRouteName is not set by customRouting function then use the automatic one
        if(empty(self::$CurrentRouteName) || (!empty($fileName) && self::$CurrentRouteName != $fileName)){
            self::$CurrentRouteName = $fileName;

            if(!str_ends_with(self::$CurrentRouteName, '-view')){
                self::$CurrentRouteName .= '-view';
            }
        }
        
        $guardResult = self::viewAccessGuard(self::$CurrentRouteName);
        
        if(!empty($guardResult->redirectViewCode)){
            $fileName = $guardResult->redirectViewCode;
        }

        self::$CurrentFileName = $fileName;

        // Get view's locale code if not passed
        if(empty($localeCode)){
            $localeCode = self::getLocaleCode($fileName);
        }

        self::$CurrentLocaleCode = $localeCode;

        // Add guard message (if any) in params
        $params = $params??[];
        if(!is_null($guardResult->statusCode)){
            $params['errorMessage'] = $guardResult->message;
            $params['statusCode'] = $guardResult->statusCode;
            $params['redirect'] = $guardResult->redirectViewCode;
        }

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
            $viewPath = self::getErrorViewPath($localeCode);
            self::$CurrentFileName = self::$ErrorView;
            // Set page not found response status
            $params['errorMessage'] = 'Page not found';
            $params['statusCode'] = 404;
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
    public static function renderLayout($viewContent = '', ?string $layout = null){
        self::$ViewContent = $viewContent;
        self::$CurrentLayout = $layout;
        
        if(is_null($layout)){
            $content = $viewContent;
        }else{
            if(empty($layout)){
                $layout = self::$DefaultLayout;
                self::$CurrentLayout = $layout;
            }

            $layoutPath = self::$ViewsDir . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.php';

            if(file_exists($layoutPath)){
                ob_start();
                include $layoutPath;
                $content = ob_get_clean();
            }else{
                $content = "Can't find layout file: $layoutPath";
            }
        }

        return $content;
    }

    /**
     * Check whether or not the current user is allowed to access specified view
     * @param string $routeName View code to check against current user permissions
     * @return GuardResult, if access is allowed $routeName is empty string, otherwise it's redirect
     */
    public static function viewAccessGuard($routeName): GuardResult {
        $guardResult = Guard::canView($routeName);

        // Save current url so we can use it later to get back to previous page
        if(!empty($guardResult->redirectViewCode)){
            $_SESSION[self::$RedirectViewSession] = Request::getURL();
        }

        return $guardResult;
    }

    /** 
     * Check whether or not the current user is allowed to execute specified controller method
     * @param string $controller Controller name
     * @param string $method Controller's method name
     * @return GuardResult true if allowed, false otherwise
    */
    public static function controllerExecutionGuard($controller, $method):GuardResult{
        return Guard::canExecute($controller, $method, self::$CurrentRouteName);
    }

    /**
     * Save current URL and redirect to the specified one
     * @param string $url Redirect URL
     */
    public static function redirect(string $url)
    {
        // Save current url for later use when needed
        $_SESSION[self::$RedirectViewSession] = Request::getURL();
        header("Location: $url");
        exit();
    }

    /**
     * Get last saved redirect URL
     * @return string Redirect code
    */
    public static function getPreviousURL(bool $clear = false)
    {
        $redirect = $_SESSION[self::$RedirectViewSession]?? '';
        
        if ($clear) {
            self::clearPreviousURL();
        }

        return $redirect;
    }

    /**
     * Clear last saved redirect URL
     */
    public static function clearPreviousURL(): void
    {
        if(!empty($_SESSION[self::$RedirectViewSession])){
            unset($_SESSION[self::$RedirectViewSession]);
        }
    }
}
?>