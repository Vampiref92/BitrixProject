<?php namespace Sprint\Migration;

use Bitrix\Main\Config\Option;
use Exception;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;

class CacheActivate20190506170600 extends SprintMigrationBase
{
    protected $description = 'Активация кеша';

    public function up()
    {
        try {
            //Активация управляемого кеша
            Option::set('main', 'component_cache_on', 'Y');

            //Активация автокеширвоания
            Option::set('main', 'component_managed_cache_on', 'Y');
        } catch (Exception $e) {
            $this->log()->error('Ошибка установки опций сайта - ' . $e->getMessage());
            return false;
        }
        return true;
    }

    public function down()
    {
        try {
            //Деактивация управляемого кеша
            Option::set('main', 'component_cache_on', 'N');

            //Деактивация автокеширвоания
            Option::set('main', 'component_managed_cache_on', 'N');
        } catch (Exception $e) {
            $this->log()->error('Ошибка установки опций сайта - ' . $e->getMessage());
            return false;
        }
        return true;
    }
}