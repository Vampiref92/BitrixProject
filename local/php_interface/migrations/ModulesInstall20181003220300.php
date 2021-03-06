<?php namespace Sprint\Migration;

use Bitrix\Main\ModuleManager;
use CModule;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;

class ModulesInstall20181003220300 extends SprintMigrationBase
{
    protected $description = 'Установка модулей';

    public function up()
    {
        /** устанавливаемые модули - установка модулей с пошаговой установкой работать не будет */
        $installModules = [
            'mobileapp',
            'search',
        ];
        /** @todo не проверено */
        //Установка модулей
        foreach ($installModules as $installModule) {
            if (!ModuleManager::isModuleInstalled($installModule) && $ob = CModule::CreateModuleObject($installModule)) {
                $ob->DoInstall();
            }
        }
        $this->log()->info('Установка модулей успешно завершена');
    }

    public function down()
    {
    }
}