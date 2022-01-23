<?php
declare (strict_types = 1);
namespace App\Core;

class ResizeResult {
    public bool $success = false;
    public string $message = '';
    public int $width = 0;
    public int $height = 0;
    public int $size = 0;
    public string $path = '';

    public function __construct(bool $success = false, string $message= '', int $width = 0, int $height = 0, int $size = 0, string $path = ''){
        $this->success = $success;
        $this->message = $message;
        $this->width = $width;
        $this->height = $height;
        $this->size = $size;
        $this->path = $path;
    }
}

class ImageResizer {
    public static function resize($imagePath, $maxWidth, $maxHeight, $quality, $outputPath = '', $shrinkOnly = true):ResizeResult{
        $isWin = (mb_strtolower(substr(PHP_OS, 0, 3)) === 'win');
        $imagePath = realpath($imagePath);

        if(!file_exists($imagePath)){
            return new ResizeResult(false, 'Path not found');
        }
    
        $maxWidth = abs(intval($maxWidth));
        $maxHeight = abs(intval($maxHeight));
    
        $cmd = 'convert ';
        
        // convert cmd may conflict on windows with convert command which Converts a FAT volume to NTFS
        if($isWin){
            $cmd = 'magick ';
        }
    
        $cmd .= '"' . $imagePath . '"';
        
        // Shrink only don not scale up image with dimensions less than specified in $maxWidth/$maxHeight
        if($shrinkOnly){
            // Get current image size
            list($width, $height) = getimagesize($imagePath);
            if($maxWidth > $width){
                $maxWidth = $width;
            }
            
            if($maxHeight > $width){
                $maxHeight = $width;
            }
        }
    
        if($maxWidth > 0 || $maxHeight > 0){
            $cmd .= ' -resize ';
    
            if($maxWidth > 0){
                $cmd .= $maxWidth;
            }
    
            if($maxHeight > 0){
                $cmd .= 'x' . $maxHeight;
            }
        }
    
        $quality = abs(intval($quality));
    
        if($quality > 100){
            $quality = 100;
        }
    
        if($quality > 0){
            $cmd .= ' -quality ' . $quality;
        }
    
        // If output path not set, use the same image path and overwrite old image
        if(empty($outputPath)){
            $outputPath = $imagePath;
        }else{
            // If output path is a directory, use the same input file name
            if(is_dir($outputPath)){
                // Make sure output path has correct format
                $outputPath = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $outputPath);
                
                if(substr($outputPath, -1) != DIRECTORY_SEPARATOR){
                    $outputPath .= DIRECTORY_SEPARATOR;
                }
                
                $outputPath .= pathinfo($imagePath, PATHINFO_BASENAME);
            }
        }
    
        $cmd .= ' "' . $outputPath . '"';
        exec($cmd, $op, $ret);
    
        // Get new image info
        list($width, $height) = getimagesize($outputPath);
        
        return new ResizeResult(
            empty($op),
            implode("\n", $op),
            $width,
            $height,
            filesize($outputPath),
            $outputPath
        );
    }
}