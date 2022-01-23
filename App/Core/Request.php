<?php
declare (strict_types = 1);
namespace App\Core;

use App\Core\File;

class Request
{
    private static array $Locales = ['en'];
    private static array $UploadErrors = [
        '',
        'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        'The uploaded file was only partially uploaded',
        'No file was uploaded',
        'Missing a temporary folder',
        'Failed to write file to disk',
        'A PHP extension stopped the file upload'
    ];

    /**
     * Set accepted locale codes
     * @param array $localeCodes Array of accepted language locale codes the path can use to execlude language locale code from the URI if needed (getURISegments)
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
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return string current locale code
     */
    public static function getLocaleCode():string{
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '')['path'];
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
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '')['path'];
        

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
     * Get request sigments (sigments are uri part separated by uri separator)
     * @param bool $ignoreLocale don't include locale sigment when true
     * @return array Array of uri segements
     */
    public static function getURISegments($ignoreLocale = true):array
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
     * @return array URL query string
     */
    public static function getQueryString():string
    {
        return $_SERVER["QUERY_STRING"];
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
        
        $isJSONContent = strtolower(getallheaders()['Content-Type']??'') == 'application/json';

        if($isJSONContent){
            $data = json_decode(file_get_contents('php://input'), true);
            // Make sure input stream is successfully decoded
            if(!is_null($data)){
                $_POST = $data;
            }
        }
        
        $cache = $_POST;

        return $cache;
    }

    private static function checkFile(string $inputName = ''):string {
        $error = '';

        // Early check for upload_max_filesize limit violattion ($_FILES is empty but CONTENT_LENGTH > 0)
		if(empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0){
			if($_SERVER['CONTENT_LENGTH'] > File::getSizeInBytes(ini_get('upload_max_filesize'))){
				return 'File size exceeds upload limits';
			}
		}

        // When POST is Empty this is an indecation exceeding the upload_max_filesize
        if(empty($_POST)){
            return 'File size exceeds upload limits';
        }

        return '';
    }

    public static function file(string $inputName = ''):File {
        if($error = self::checkFile($inputName)){
            return new File('', '', '', $error);
        }
        
        $file = $_FILES[$inputName]??null;
    
        if(empty($file)){
            return new File('', '', '', 'File not recognized');
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
            return [new File('', '', '', 'File not recognized')];
        }

        // If one file only is uploaded convert it to a multiple-like upload
        if(!is_array($files['name'])){
            $files = array_map(function($item){
                return [$item];
            }, $files);
        }

        for($i = 0; $i < count($files['name']); $i++){
            if(!is_uploaded_file($files['tmp_name'][$i])){
                $arrFiles[] = new File('', '', '', 'File not recognized');
            }

            $arrFiles[] = new File($files['name'][$i], $files['type'][$i], $files['tmp_name'][$i], self::$UploadErrors[$files['error'][$i]], $files['size'][$i]);  
        }
    
        return $arrFiles;
   
    }

    public static function isFileSubmitted(string $inputName = ''): bool {
        if(empty($inputName)){
            return false;
        }
        
        // If file not selected in the input field it will be included as POST var not as file var
        if(array_key_exists($inputName, self::body())){
            return false;
        }
        
        // in case of a singl file upload will get an array of one item
        $files = self::files($inputName);

        if(empty($files)){
            return false;
        }

        // Make sure at least one file is successfully uploaded
        $okFiles = array_filter($files, function(File $file){
            return empty($file->error) && $file->size > 0;
        });

        return count($okFiles) > 0;
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

    private static function upload(File $file, string $uploadDir, string $fileName = '', string $extension = '', array $acceptedMimeTypes = [], bool $makeDir = false): File {
        if($file->error){
            return $file;
        }

        // Check for accepted mime types
        if(!empty($acceptedMimeTypes)){
            // Check for mimes like image/*, audio/*, ...
            $result = array_filter($acceptedMimeTypes, function($item)use($file){
                return ($file->type == $item || explode('/', $file->type)[0] .'/*' == $item);
            });

            if(empty($result)){
                $file->error = 'File type not accepted';
                return $file;
            }
        }

        if(empty($fileName)){
            $fileName = $file->name;
        }

        if(empty($extension)){
            // Get extenstion from the original file name
            $extension = '.' . File::getExtension($file->name);
        }else{
            $extension = '.' . str_replace('.', '', $extension);
        }

        // Replace original file name with the new one 
        $file->name = $fileName . $extension;

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
        
        $file->path = $uploadDir . $file->name;

        // Move uploaded photo
        if(!move_uploaded_file($file->tmpName, $file->path)){
            $file->error = 'Cannot move file to upload directory';
        }

        return $file;
    }
}
