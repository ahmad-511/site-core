<?php
declare (strict_types = 1);

namespace App\Core;
use App\Core\Result;

class Response{
    /**
     * Set response status code
     * @param int $statusCode response status code
     */
    public static function setStatus(int $statusCode = null) {
        http_response_code($statusCode);
    }

    /**
     * Send response to client
     * @param Result|string $result data object to be sent
     * @param int $statusCode response status code
     */
    public static function send($result, int $statusCode = 200) {
        self::setStatus($statusCode);
        
        // If http headers already sent we can not send necessary json headers
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        echo json_encode($result, JSON_THROW_ON_ERROR, 512);
    
        exit();
    }
}