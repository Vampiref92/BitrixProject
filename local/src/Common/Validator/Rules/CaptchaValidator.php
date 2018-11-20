<?php

namespace ProjectName\Common\Validator\Rules;

use App\Common\Enum\External;
use Rakit\Validation\Rule;
use Vf92\ReCaptcha\ReCaptcha;

class CaptchaValidator extends Rule
{
    public function check($value)
    {
        return ReCaptcha::checkCaptcha(External::RECAPTCHA_SECRET_KEY, $value);
    }
}