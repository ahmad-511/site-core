<?php
declare (strict_types = 1);
namespace App\Model;

use App\Core\Model;
use App\Core\Captcha;

class CaptchaCode extends Model {

    public static function Get(array $params = []){
        $captcha = new Captcha();
        $captchaCode = $captcha->Generate();

        $name = $params[0]??'captcha_code';
        $_SESSION[$name] = $captchaCode->code;

        header('Content-Type: image/png');
        imagepng($captchaCode->image);
    }
}