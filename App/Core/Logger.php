<?php
declare (strict_types = 1);

namespace App\Core;

class Logger {
    private static string $LogsPath = __DIR__ . '.logs';
    
    public static function setLogPath(string $path){
        self::$LogsPath = $path;
    }

    /**
     * Log data to a lof file
     * @param string $data Data to be written to the log
     * @return bool true if loggin was successfull, false otherwise
     */
    public static function log($data){
        if(is_writable(self::$LogsPath)){
            return file_put_contents(self::$LogsPath . 'log_'.date('Y-m-d').'.log', $data . "\r\n", FILE_APPEND);
        }

        return false;
    }
}