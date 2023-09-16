<?php
declare (strict_types = 1);
namespace App\Core;

use App\Core\File;
use stdClass;

class Request
{
    private static array $Locales = ['en'];
    private static array $UploadErrors = [
        '',
        'The uploaded file exceeded the server configuration for upload max file size',
        'The uploaded file exceeded the max file size specified in the HTML form',
        'The uploaded file was only partially uploaded',
        'No file was uploaded',
        'Missing a temporary folder',
        'Failed to write file to disk',
        'A PHP extension stopped the file upload'
    ];

    /**
     * Set accepted locale codes
     * @param array $localeCodes Array of accepted language locale codes the path can use to exclude language locale code from the URL if needed (getURLSegments)
     * @return void
     */
    public static function setLocales(array $localeCodes):void{
        self::$Locales = $localeCodes;
    }

    /**
     * Get current request method
     * @return string Request method (GET, POST,...)
     */
    public static function getMethod():string{
        // This is mainly used for PUT method with multipart/form-data content type where php can't handle submitted data
        // php can upload by only using post method and when content-type is multipart/form-data
        // By using Method header we can keep using the put route while we're actually sending a post request
        $method = self::getHeader('Method', '');
        return !empty($method)?$method: $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get current request url
     * @return string Request url
     */
    public static function getURL():string{
        return $_SERVER['REQUEST_URI']??'';
    }

    /**
     * @return string current locale code
     */
    public static function getLocaleCode():string{
        $path = parse_url(self::getURL())['path'];
        $path = trim($path, '/');
        $path = explode('/', $path, 2);

        if(in_array(strtolower($path[0]), self::$Locales)) {
            return array_shift($path);
        }
        
        return '';
    }

    /**
     * @return string The path part of the current request
     */
    public static function getPath($ignoreLocale = true):string{
        $path = parse_url(self::getURL())['path'];
        

        if($ignoreLocale) {
            $path = trim($path, '/');
            $path = explode('/', $path, 2);

            if(in_array(strtolower($path[0]), self::$Locales)) {
                array_shift($path);
            }
            
            $path = '/' . implode('/', $path);
        }
        
        return $path;
    }

    /**
     * Get request segments (segments are url part separated by url separator)
     * @param bool $ignoreLocale don't include locale segment when true
     * @return array Array of url segments
     */
    public static function getURLSegments($ignoreLocale = true):array
    {
        $path = self::getPath($ignoreLocale);
        $path = trim($path, '/');
        $segs = explode('/', $path);

        return $segs;
    }

    /**
     * @return array GET params
     */
    public static function getQueryParams():array
    {
        return $_GET;
    }

    /**
     * Get specified query variable value
     * @param string $key Query variable variable name
     * @param ?string $default Default value if variable name not found
     * @return string Value of specified query variable or default if key not found
     */
    public static function query(string $key, $default = null)
    {
        return $_GET[$key]??$default;
    }

    /**
     * @return array URL query string
     */
    public static function getQueryString():string
    {
        return $_SERVER["QUERY_STRING"];
    }

    /**
     * Get all request headers
     * @return array array of headers as key/value pairs
     */
    public static function getAllHeaders():array
    {
        return getallheaders();
    }

    /**
     * Get request header
     * @param string header key
     * @return string header value
     */
    public static function getHeader(string $str, string $default = ''):string
    {
        $headers = self::getAllHeaders();

        return $headers[$str]??$default;
    }

    /**
     * Get data payload from $_POST or from input stream when content type is json
     * @return array POST params
     */
    public static function body():array
    {
        static $cache = null;
        
        if(!empty($cache)){
            return $cache;
        }
        
        $data = file_get_contents('php://input');
        
        $isJSONContent = strtolower(getallheaders()['Content-Type']??'') == 'application/json';
        
        if($isJSONContent){
            $data = json_decode($data, true);

            // Make sure input stream is successfully decoded
            if(!is_null($data)){
                $_POST = $data;
            }
        }elseif(!empty($data)){ // input stream will be empty for multipart/form-data using PUT method
            mb_parse_str($data, $_POST);
        }
        
        $cache = $_POST;

        return $cache;
    }

    private static function checkFile(string $inputName = ''):string {
        // Early check for upload_max_filesize limit violation ($_FILES is empty but CONTENT_LENGTH > 0)
		if(empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0){
			if($_SERVER['CONTENT_LENGTH'] > File::getSizeInBytes(ini_get('upload_max_filesize'))){
				return 'File size exceeds upload limits';
			}
		}

        // When POST is Empty this is an indication exceeding the upload_max_filesize
        if(empty($_POST)){
            return 'File size exceeds upload limits';
        }

        return '';
    }

    public static function file(string $inputName = ''):?File {
        if($error = self::checkFile($inputName)){
            return new File('', '', '', $error);
        }
        
        $file = $_FILES[$inputName]??null;
    
        if(empty($file)){
            return null;
        }

        // Get first file if multiple ones are uploaded
        if(is_array($file['name'])){
            $file = array_map(function($item){
                return $item[0];
            }, $file);
        }

        if(!is_uploaded_file($file['tmp_name'])){
            return new File('', '', '', 'File not recognized');
        }
        
        return new File($file['name'], $file['type'], $file['tmp_name'], self::$UploadErrors[$file['error']], $file['size']);  
    }
    
    public static function files(string $inputName = ''):array {
        $arrFiles = [];
        $objFile = new File();

        if($objFile->error = self::checkFile($inputName)){
            $arrFiles[] = $objFile;
            return $arrFiles;
        }
        
        $files = $_FILES[$inputName]??null;

        if(empty($files)){
            return [];
        }

        // If one file only is uploaded convert it to a multiple-like upload
        if(!is_array($files['name'])){
            $files = array_map(function($item){
                return [$item];
            }, $files);
        }

        for($i = 0; $i < count($files['name']); $i++){
            $arrFiles[] = new File($files['name'][$i], $files['type'][$i], $files['tmp_name'][$i], self::$UploadErrors[$files['error'][$i]], $files['size'][$i]);
        }
    
        return $arrFiles;
    }

    public static function isFileSubmitted(string $inputName = ''): stdClass {
        $status = new stdClass();
        $status->success = 0;
        $status->fail = 0;
        $status->errors = [];

        if(empty($inputName)){
            return $status;
        }
        
        // If file not selected in the input field it will be included as POST var not as file var
        if(array_key_exists($inputName, self::body())){
            return $status;
        }
        
        // in case of a single file upload will get an array of one item
        $files = self::files($inputName);

        if(empty($files)){
            return $status;
        }

        // Make sure at least one file is successfully uploaded
        $status->success = count(array_filter($files, function(File $file){
            return empty($file->error) && $file->size > 0;
        }));

        $failFiles = array_filter($files, function(File $file){
            return !empty($file->error);
        });

        $status->fail = count($failFiles);

        $status->errors = array_map(function($file){
            return $file->name . ': ' . $file->error;
        }, $failFiles);

        return  $status;
    }

    public static function uploadFile(string $inputFieldName, string $uploadDir, string $fileName = '', string $extension = '', array $acceptedMimeTypes = [], bool $makeDir = false): File{
        $file = self::file($inputFieldName);

        return self::upload($file, $uploadDir, $fileName, $extension, $acceptedMimeTypes, $makeDir);
    }

    public static function uploadFiles(string $inputFieldName, string $uploadDir, string $fileName = '', string $extension = '', array $acceptedMimeTypes = [], bool $makeDir = false): array{
        $files = self::files($inputFieldName);
        $arrFiles = [];

        foreach($files as $i => $file){
            $arrFiles[] = self::upload($file, $uploadDir, $fileName.$i, $extension, $acceptedMimeTypes, $makeDir);
        }

        return $arrFiles;
    }

    private static function upload(File $file, string $uploadDir, string $distFileName = '', string $extension = '', array $acceptedMimeTypes = [], bool $makeDir = false): File {
        if($file->error){
            return $file;
        }

        // Check for accepted mime types
        if(!empty($acceptedMimeTypes)){
            // Check for mimes like image/*, audio/*, ...
            $result = array_filter($acceptedMimeTypes, function($item)use($file){
                return ($file->mimeType == $item || explode('/', $file->mimeType)[0] .'/*' == $item);
            });

            if(empty($result)){
                $file->error = 'File type not accepted';
                return $file;
            }
        }

        if(empty($distFileName)){
            $distFileName = $file->name;
        }

        if(empty($extension)){
            // Get extension from the original file name
            $extension = '.' . File::getExtension($file->name);
        }else{
            $extension = '.' . str_replace('.', '', $extension);
        }

        // Create necessary directory for uploaded file
        if(!is_dir($uploadDir)){
            if($makeDir){
                if(!mkdir($uploadDir, 0777, true)){
                    $file->error = 'Cannot create upload directory';
                }
            }else{
                $file->error = 'Upload directory not exist';
            }
        }
        
        $file->path = $uploadDir . $distFileName . $extension;

        // Move uploaded photo
        if(!move_uploaded_file($file->tmpName, $file->path)){
            $file->error = 'Cannot move file to upload directory';
        }

        return $file;
    }

    public static function isAjax(): bool
    {
        return self::getHeader('X-Requested-With') == 'XMLHttpRequest';
    }

    public static function accept($mimeType = '', $acceptAny = false): bool
    {
        $acceptHeaders = self::getHeader('Accept', '');
        $acceptHeaders = str_replace(';', ',', $acceptHeaders);
        $acceptHeaders = explode(',', $acceptHeaders);

        if($acceptAny && in_array('*/*', $acceptHeaders)) {
            return true;
        }
        
        return in_array($mimeType, $acceptHeaders);
    }
}
