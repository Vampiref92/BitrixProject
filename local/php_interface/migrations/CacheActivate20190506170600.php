<?php namespace Sprint\Migration;

use Bitrix\Main\Config\Option;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;

class CacheActivate20190506170600 extends SprintMigrationBase
{
    protected $description = 'Активация кеша';

    public function up()
    {
        //Активация управляемого кеша
        Option::set('main', 'component_cache_on', 'Y');

        //Активация автокеширвоания
        Option::set('main', 'component_managed_cache_on', 'Y');
    }

    public function down()
    {
        //Деактивация управляемого кеша
        Option::set('main', 'component_cache_on', 'N');

        //Деактивация автокеширвоания
        Option::set('main', 'component_managed_cache_on', 'N');
    }
}