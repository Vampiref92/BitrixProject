<?php

namespace Sprint\Migration;

use Bitrix\Main\Config\Option;
use Bitrix\Main\SiteDomainTable;
use Bitrix\Main\SiteTable;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;
use Vf92\MiscUtils\EnvType;

class AddSiteSettings20180913121900 extends SprintMigrationBase
{
    protected $description = 'Задание настроек сайта';

    public function up()
    {
        //Изменяем настройки сайта
        $siteId = 's1';
        $siteName = 'SiteName';
        $siteNameFull = 'SiteNameFull';
        $serverName = 'site_address.ru';
        /** устанавливать ли в главном модуле email, site_name, server_name */
        $setMainModuleSiteSettings = true;
//        $email = 'info@' . $serverName;
        $email = 'email';

        //Установка настроек сайта
        $res = SiteTable::update($siteId, [
            'NAME'        => $siteName,
            'SITE_NAME'   => $siteNameFull,
            'SERVER_NAME' => $serverName,
            'EMAIL'       => $email,
        ]);
        if ($res->isSuccess()) {
            $this->log()->info('Настрйоки сайта успешно изменены');
        } else {
            $this->log()->error('Ошибка сохранения настреок сайта: ' . implode('; ', $res->getErrorMessages()));
            return false;
        }

        //Установка доменов сайта
        $res = $currentSites = SiteDomainTable::query()->setSelect(['DOMAIN'])
            ->where('LID', $siteId)
            ->exec();
        $currentSites = [];
        while ($item = $res->fetch()) {
            $currentSites[] = $item['DOMAIN'];
        }
        $addSites = [
            $serverName,
            'www.' . $serverName,
        ];
        foreach ($addSites as $addSite) {
            if (!\in_array($addSite, $currentSites, true)) {
                $res = SiteDomainTable::add(['LID' => $siteId, 'DOMAIN' => $addSite]);
                if ($res->isSuccess()) {
                    $this->log()->info('Успешно добавлен домен ' . $addSite);
                } else {
                    $this->log()->error('Ошибка при добавлении домена ' . $addSite . ': ' . implode(
                            '; ',
                            $res->getErrorMessages()
                        ));
                    return false;
                }
            }
        }

        if ($setMainModuleSiteSettings) {
            Option::set('main', 'site_name', $siteNameFull);
            Option::set('main', 'server_name', $serverName);
            Option::set('main', 'email_from', $email);
        }
        //быстрая отдача по nginx
        Option::set('main', 'bx_fast_download', 'Y');

        //сжатие и оптимизация css и js
        Option::set('main', 'optimize_css_files', 'Y');
        Option::set('main', 'optimize_js_files', 'Y');
        Option::set('main', 'use_minified_assets', 'Y');
        Option::set('main', 'move_js_to_body', 'Y');
        Option::set('main', 'compres_css_js_files', 'Y');

        //Россия
        Option::set('main', 'phone_number_default_country', '1');
        //капча при регистрации
        Option::set('main', 'captcha_registration', 'Y');

        //включаем полное логирование
        Option::set('main', 'event_log_logout', 'Y');
        Option::set('main', 'event_log_login_success', 'Y');
        Option::set('main', 'event_log_login_fail', 'Y');
        Option::set('main', 'event_log_register', 'Y');
        Option::set('main', 'event_log_register_fail', 'Y');
        Option::set('main', 'event_log_password_request', 'Y');
        Option::set('main', 'event_log_password_change', 'Y');
        Option::set('main', 'event_log_user_edit', 'Y');
        Option::set('main', 'event_log_user_delete', 'Y');
        Option::set('main', 'event_log_user_groups', 'Y');
        Option::set('main', 'event_log_group_policy', 'Y');
        Option::set('main', 'event_log_module_access', 'Y');
        Option::set('main', 'event_log_file_access', 'Y');
        Option::set('main', 'event_log_task', 'Y');
        Option::set('main', 'event_log_marketplace', 'Y');

        // установка для разработки
        if (EnvType::isDev()) {
            Option::set('main', 'update_devsrv', 'Y');
        } else {
            Option::set('main', 'update_devsrv', 'N');
        }

        $this->log()->info('Настройк модулей успешно изменены');
        return true;
    }

    public function down()
    {
    }
}
