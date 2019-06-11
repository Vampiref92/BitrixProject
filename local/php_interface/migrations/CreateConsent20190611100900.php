<?php namespace Sprint\Migration;

use Bitrix\Main\UserConsent\Agreement;
use Symfony\Component\Config\Definition\Exception\Exception;
use Vf92\BitrixUtils\Consent\ConsentHelper;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;

class CreateConsent20190611100900 extends SprintMigrationBase
{
    protected $description = 'Создание соглашения для сайта';

    public function up()
    {
        $code = 'concent_code';
        $itemId = 0;

        try {
            $itemId = ConsentHelper::getConsentId($code);
        } catch (\Exception $e) {
            // Не обрабатываем ошибку - если не найден то установим
        }
        $agreement = new Agreement($itemId);
        $data = [
            'NAME'           => 'name',
            'CODE'           => $code,
            'ACTIVE'         => 'Y',
            'TYPE'           => 'S',
            'LANGUAGE_ID'    => 'ru',
            'DATA_PROVIDER'  => '',
            'AGREEMENT_TEXT' => '',
            'LABEL_TEXT'     => 'Нажимая на кнопку, я принимаю условия соглашения.',
            'FIELDS'         => [
                'EMAIL' => 'info@email.ru',
            ],
        ];
        $agreement->mergeData($data);
        $agreement->save();
    }

    public function down()
    {
    }
}