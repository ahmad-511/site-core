<?php
declare (strict_types = 1);
namespace App\Model;

use App\Core\Localizer as L;
use App\Core\Router;
use App\Core\Mailer;
use App\Core\Model;
use App\Core\Result;
use App\Core\Template;
use App\Core\Validator;
use App\Core\ValidationRule;

class Contact extends Model
{
    private $name;
    private $email;
    private $message;
    private $captcha_code;
    private $validator;

    public function __construct(array $props = [])
    {
        $this->name = $props['name'] ?? '';
        $this->email = $props['email'] ?? '';
        $this->message = $props['message'] ?? '';
        $this->captcha_code = $props['captcha_code'] ?? '';

        $this->validator = new Validator($this);
        $this->validator->add('name', 'Name is missing', ValidationRule::notEmpty());
        $this->validator->add('email', 'Email is not valid', ValidationRule::email());
        $this->validator->add('message', 'Message is not valid', ValidationRule::notEmpty());
        $this->validator->add('captcha_code', 'Invalid captcha code', function($value){
            return strtolower($value) == strtolower($_SESSION['captcha_code']??'fake'.random_int(0, 999));
        });       
    }

    public function Support(array $params = [])
    {
        if($dataErr = $this->validator->validate()){
            return new Result(
                $dataErr,
                L::loc('Some data are missing or invalid'),
                'validation_error',
                ''
            );
        }

        unset($_SESSION['captcha_code']);

        $tpl = new Template('contact-us');
        if(!$tpl){
            return new Result(
                null,
                L::loc('Mail template cannot be loaded', Router::getCurrentLocaleCode()),
                'error',
                ''
            );
        }

        $params = [
            'email_body' =>  $tpl->render([
                'name' => $this->name,
                'email' => $this->email,
                'message' => $this->message,
            ])
        ];

        $isSent = Mailer::sendTemplate(SUPPORT_EMAIL, L::loc('Support message'), 'general', $params);

        if ($isSent) {
            return new Result(
                null,
                L::loc('Thank you for contacting us', Router::getCurrentLocaleCode()),
                'success',
                ''
            );
        }

        return new Result(
            null,
            L::loc('Mail server is down', Router::getCurrentLocaleCode()),
            'error',
            ''
        );
    }
}
