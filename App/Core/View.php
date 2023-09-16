<?php
declare (strict_types = 1);

namespace App\Core;

class View {
    public string $fileName = '';
    public array $params = [];
    public string $localeCode = '';
    public ?string $layout = '';
    public int $statusCode = 200;
    public array $headers = [];

    public function __construct(string $fileName, array $params = [], string $localeCode = '', ?string $layout = '', int $statusCode = 200, array $headers = [])
    {
        $this->fileName = $fileName;
        $this->params = $params;
        $this->localeCode = $localeCode;
        $this->layout = $layout;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
}