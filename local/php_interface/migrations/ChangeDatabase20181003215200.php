<?php namespace Sprint\Migration;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\DB\MysqliConnection;
use Vf92\BitrixUtils\Config\Dbconn;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;

class ChangeDatabase20181003215200 extends SprintMigrationBase
{
    protected $description = 'Изменение соединения с БД';

    public function up()
    {
        $dbList = [
            'default' => [
                'host'     => 'localhost',
                'database' => 'db',
                'login'    => 'user',
                'password' => 'pass',
            ],
        ];

        //Изменяем конфиг
        if (isset($dbList['default']) && !empty($dbList['default'])) {
            $configuration = Configuration::getInstance();
            $baseConfig = [
                'className' => MysqliConnection::class,
                'options'   => 2,
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

            $dbConn = Dbconn::get();
            $dbConn['db']['Host'] = $dbList['default']['host'];
            $dbConn['db']['Login'] = $dbList['default']['login'];
            $dbConn['db']['Password'] = $dbList['default']['password'];
            $dbConn['db']['Name'] = $dbList['default']['database'];
            $dbConn['define']['db']['BX_USE_MYSQLI'] = true;
            Dbconn::save($dbConn);

            $this->log()->info('Настройки Бд успешно сохранены');
        }
    }

    public function down()
    {
    }
}