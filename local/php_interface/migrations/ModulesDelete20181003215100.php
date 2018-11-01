<?php namespace Sprint\Migration;

use Bitrix\Main\ModuleManager;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;

class ModulesDelete20181003215100 extends SprintMigrationBase
{
    protected $description = 'Удаление модулей';

    public function up()
    {
        /** удаляемые модули */
        $deleteModules = [
            'blog',
            'mobileapp',
            'vote',
            'translate',
            'subscribe',
            'search',
            'socialservices',
            'forum',
            'photogallery',
        ];
        //Удаление неиспользуемых модулей
        foreach ($deleteModules as $deleteModule) {
            if (ModuleManager::isModuleInstalled($deleteModule)) {
                ModuleManager::delete($deleteModule);
            }
        }
        $this->log()->info('Удаление модулей успешно завершено');
    }

    public function down()
    {
    }
}