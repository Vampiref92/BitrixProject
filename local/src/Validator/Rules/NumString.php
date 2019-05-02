<?php

namespace App\Common\Validator\Rules;

use Rakit\Validation\Rule;

class NumString extends Rule
{
    protected $message = "Неверный формат поля";

    public function check($value)
    {
        return preg_match('#^[0-9]+$#', trim($value));
    }
}
