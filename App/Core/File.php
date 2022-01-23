<?php
declare (strict_types = 1);
namespace App\Core;

class File {
    public string $name;
    public string $type;
    public string $tmpName;
    public string $error;
    public int $size;
    public string $path;

    public function __construct(string $name = '', string $type = '', string $tmpName = '', string $error = '', int $size = 0, string $path = '')
    {
        $this->name = $name;
        $this->type = $type;
        $this->tmpName = $tmpName;
        $this->error = $error;
        $this->size = $size;
        $this->path = $path;
    }

    /**
     * Extract file extension
     * @param string $fileName the file name we need to get its extension
     * @return string file extension
     */
    public static function getExtension(string $fileName):string{
        return pathinfo($fileName, PATHINFO_EXTENSION)??'';
    }

    /**
     * Convert human readable size to bytes
     * @param string $str human readable size (1M, 43K, 2G, 7T...)
     * @return int the value in bytes
     */
    public static function getSizeInBytes($str): int {
        $str = trim($str);
        $u = strtolower(substr($str, -1, 1));
        $bytes = intval($str);
        
        $units = ['t', 'g', 'm', 'k'];
        $uIndex = array_search($u, $units);

        if($uIndex !== false){
            for($i = $uIndex; $i < count($units); $i++){
                $bytes *= 1024;
            }
        }
    
        return $bytes;
    }

    /**
     * Convert human readable size to bytes
     * @param string $str human readable size (1M, 43K, 2G, 7T...)
     * @return int the value in bytes
     */
    public static function getFriendlySize(int $bytes): string {       
        $units = ['K', 'M', 'G', 'T'];
        $unit = 'B';

        for($i = 0; $i < count($units); $i++){
            if($bytes < 1024){
                break;
            }
            
            $bytes /= 1024;
            $unit = $units[$i];
        }
    
        return round($bytes, 2) . $unit;
    }

    public static function remove(string $path): bool{
        if(!file_exists($path)){
            return false;
        }

        return unlink($path);
    }
}
?>