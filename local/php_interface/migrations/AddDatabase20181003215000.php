<?php namespace Sprint\Migration;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\DB\MysqliConnection;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;

class AddDatabase20181003215000 extends SprintMigrationBase
{
    protected $description = 'Добавление нового соединения';

    public function up()
    {
        $dbList = [
            'custom' => [
                'host'     => 'localhost',
                'database' => 'db',
                'login'    => 'user',
                'password' => 'pass',
            ],
        ];

        //Изменяем конфиг
        if (!empty($dbList)) {
            $configuration = Configuration::getInstance();
            $baseConfig = [
                'className' => MysqliConnection::class,
                'options'   => 2
            ];
            foreach ($dbList as &$item) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $item = \array_merge($baseConfig, $item);
            }
            unset($item);
            $additionalConfig = $dbList;

            $dbList = array_merge($configuration->get('connections'), $additionalConfig);

            $configuration->addReadonly('connections', $dbList);
            $configuration->saveConfiguration();

            $this->log()->info('Настройки Бд успешно сохранены');
        }
    }

    public function down()
    {

    }
}