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
     * Send response headers
     * @param array $headers the headers to be set
     * @return bool false when headers are sent, true otherwise
     */
    public static function setHeaders(array $headers = []):bool{
        if(headers_sent()){
            // Check if sent headers includes all user headers
            $headersList = headers_list();

            foreach($headers as $header){
                // If one user header at least not in the sent headers list we have to stop
                if(!in_array($header, $headersList)){
                    return false;
                }
            }

            // All user headers are included in already sent headers list so we can accept that
            return true;
        }
        
        foreach($headers as $header){
            header($header);
        }

        return true;
    }

    /**
     * Send response to client
     * @param Result|string $result data object to be sent
     * @param int $statusCode response status code
     */
    public static function send($result, int $statusCode = 200, array $headers = ['Content-Type: application/json']) {
        self::setStatus($statusCode);
        self::setHeaders($headers);

        echo json_encode($result, JSON_THROW_ON_ERROR, 512);
    
        exit();
    }
}