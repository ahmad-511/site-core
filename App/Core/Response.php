<?php
declare (strict_types = 1);

namespace App\Core;

class Response{
    public $data = null;
    public int $statusCode = 200;
    public array $headers = [];

    public function __construct($data, int $statusCode = 200, array $headers = [])
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

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
     * Send raw response to client
     * @param any $data raw data to be sent
     * @param int $statusCode response status code
     * @param aray $headers response headers to be set
     */
    public static function sendRaw($data, int $statusCode = 200, array $headers = []) {
        self::setStatus($statusCode);
        self::setHeaders($headers);
        
        if(gettype($data) == 'object'){
            $data = json_encode($data, JSON_THROW_ON_ERROR, 512);
        }

        echo $data;

        exit();
    }

    /**
     * Send Respnse object to client
     * @param Reponse $response object to be sent
     */
    public static function send(Response $response) {
        self::sendRaw($response->data, $response->statusCode, $response->headers);
    }

    /**
     * Send json response to client
     * @param Object $data object to be sent
     * @param int $statusCode response status code
     */
    public static function json($data, int $statusCode = 200, array $headers = []) {
        $data = json_encode($data, JSON_THROW_ON_ERROR, 512);
        if(!in_array('Content-Type: application/json', $headers)){
            $headers[] = 'Content-Type: application/json';
        }

        self::sendRaw($data, $statusCode, $headers);
    }
}