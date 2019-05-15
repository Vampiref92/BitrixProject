<?php namespace App\Common\Validator;

use App\Common\Validator\Rules\CaptchaValidator;
use App\Common\Validator\Rules\NumString;
use App\Common\Validator\Rules\PhoneValidator;
use Rakit\Validation\Validator;

/**
 * Class ValidatorProvider
 * @package App\CountKuhniUnited
 */
class ValidatorProvider
{
    public static $validator;

    /**
     * ValidatorProvider constructor.
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function __construct()
    {
        self::$validator = new Validator;

        self::$validator->setMessage('email', 'Email адрес указан неверно');
        self::$validator->setMessage('required', 'Поле :attribute обязательно для заполнения');
        self::$validator->setMessage('uploaded_file', 'Неверный формат загруженного файла');
        self::$validator->setMessage('date', 'Дата введена неверно');

        self::$validator->addValidator('phone', new PhoneValidator());
        self::$validator->addValidator('recaptcha', new CaptchaValidator());
        self::$validator->addValidator('numString', new NumString());
    }
}