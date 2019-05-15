<?php

namespace App\Common\Validator\Rules;

use Rakit\Validation\Rule;

class PhoneValidator extends Rule
{
    protected $message = "Неверный формат номера телефона";

    public function check($value)
    {
        return preg_match('#^\+\d+\s\(\d{3}\)\s\d{3}\-\d{2}\-\d{2}$#', trim($value));
    }
}
