<?php namespace Sprint\Migration;

use Bitrix\Main\Config\Configuration;
use Vf92\BitrixUtils\Config\Dbconn;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;

class ConvertToUtf820180926160900 extends SprintMigrationBase
{
    protected $description = '';

    public function up()
    {
//        define("BX_UTF", true);
        $dbconnList = Dbconn::get();
        $dbconnList['define']['project']['BX_UTF'] = false;
        Dbconn::save($dbconnList);

        $config = Configuration::getInstance();
        $config->addReadonly('utf_mode', ['value' => true, 'readonly' => true]);
        $config->saveConfiguration();

        /** @todo правка настроек сайта */
        /** @todo переконвертация файлов */
        /** @todo переконвертация базы */
        /** @todo правка autoconnect */
    }

    public function down()
    {
    }
}