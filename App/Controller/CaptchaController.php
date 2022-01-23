<?php
declare (strict_types = 1);
namespace App\Controller;

use App\Core\Captcha;
use App\Core\Controller;

class CaptchaController extends Controller {

    public static function Get(array $params = []){
        $captcha = new Captcha();
        $captchaResult = $captcha->Generate();

        $name = $params['captcha_name']??'captcha_code';
        $_SESSION[$name] = $captchaResult->code;

        header('Content-Type: image/png');
        imagepng($captchaResult->image);
    }
}