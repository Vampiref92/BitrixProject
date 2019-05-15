<?php namespace Sprint\Migration;

use function array_merge;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\DB\MysqliConnection;
use Bitrix\Main\InvalidOperationException;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;

class DatabaseAdd20181003215000 extends SprintMigrationBase
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
                $item = array_merge($baseConfig, $item);
            }
            unset($item);
            $additionalConfig = $dbList;

            $dbList = array_merge($configuration->get('connections'), $additionalConfig);

            $configuration->addReadonly('connections', $dbList);
            try {
                $configuration->saveConfiguration();
            } catch (InvalidOperationException $e) {
                $this->log()->error('Ошибка сохранения конфигурации - ' . $e->getMessage());
                return false;
            }

            $this->log()->info('Настройки Бд успешно сохранены');
        }
        return true;
    }

    public function down()
    {

    }
}