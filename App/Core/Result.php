<?php
namespace App\Core;

class Result
{
    public $data;
    public $message;
    public $messageType;
    public $redirect;
    public $metaData;

    public function __construct($data = null, $message = '', $messageType = 'info', $redirect = '', $metaData = null)
    {
        $this->data = $data;
        $this->message = $message;
        $this->messageType = $messageType;
        $this->redirect = $redirect;
        $this->metaData = $metaData;
    }
}
