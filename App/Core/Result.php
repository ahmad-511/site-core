<?php
namespace App\Core;

class Result
{
    public $data;
    public $message;
    public $messageType;
    public $redirect;
    public $metaData;
    public int $statusCode = 200;
    public array $headers = [];

    public function __construct($data = null, $message = '', $messageType = 'info', $redirect = '', $metaData = null, int $statusCode = 200, array $headers = [])
    {
        $this->data = $data;
        $this->message = $message;
        $this->messageType = $messageType;
        $this->redirect = $redirect;
        $this->metaData = $metaData;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
}
