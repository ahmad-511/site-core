<?php
declare (strict_types = 1);
namespace App\Core;

class GuardResult{
    public bool $isAllowed;
    public string $redirectViewCode;
    public string $message;
    public ?int $statusCode;

    public function __construct(bool $isAllowed = true, string $redirectViewCode = '', string $message = '', ?int $statusCode = null)
    {
        $this->isAllowed = $isAllowed;
        $this->redirectViewCode = $redirectViewCode;
        $this->message = $message;
        $this->statusCode = $statusCode;
    }
}
?>