<?php

namespace ProjectName\Common\Validator\Rules;

use Rakit\Validation\Rule;

class CyrillicAlpha extends Rule
{
    protected $message = "Для поля доступны только кириллические символы";

    public function check($value)
    {
        return is_string($value) && preg_match('/^[\sA-Za-zА-Яа-яЁё]+$/u', $value);
    }
}
