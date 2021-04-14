<?php
declare (strict_types = 1);
namespace App\Core;

class Route{
    public string $method = '';
    public string $path = '';
    public $controller = null;
    public string $name = '';

    public function __construct($method, $path, $controller, $name = '')
    {
        $this->method = $method;
        $this->path = $path;
        $this->controller = $controller;
        $this->name = $name;
    }
}
?>