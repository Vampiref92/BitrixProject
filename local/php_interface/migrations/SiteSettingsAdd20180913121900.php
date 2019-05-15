<?php

namespace Sprint\Migration;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\GroupTable;
use Bitrix\Main\SiteDomainTable;
use Bitrix\Main\SiteTable;
use Bitrix\Main\SiteTemplateTable;
use Vf92\BitrixUtils\Config\Dbconn;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;
use Vf92\MiscUtils\EnvType;

class SiteSettingsAdd20180913121900 extends SprintMigrationBase
{
    protected $description = 'Задание настроек сайта';

    public function up()
    {
        //Изменяем настройки сайта
        $siteId = 's1';
        $siteName = 'SiteName';
        $siteNameFull = 'SiteNameFull';
        $serverName = 'site_address.ru';
        $templateName = 'main';
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

        //Установка шаблона сайта
        SiteTemplateTable::add([
            'SITE_ID' => $siteId,
            'CONDITION' => '',
            'SORT' => 1,
            'TEMPLATE' => $templateName,
        ]);

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
        if (!EnvType::isProd()) {
            Option::set('main', 'update_devsrv', 'Y');
        } else {
            Option::set('main', 'update_devsrv', 'N');
        }

        $this->log()->info('Настройк модулей успешно изменены');

        /** настройка повышенного уровня группы администраторов */
        $securityPolice = [
            'SESSION_TIMEOUT'      => '15',
            'SESSION_IP_MASK'      => '255.255.255.255',
            'MAX_STORE_NUM'        => '1',
            'STORE_IP_MASK'        => '255.255.255.255',
            'STORE_TIMEOUT'        => '4320',
            'CHECKWORD_TIMEOUT'    => '60',
            'PASSWORD_LENGTH'      => '10',
            'PASSWORD_UPPERCASE'   => 'Y',
            'PASSWORD_LOWERCASE'   => 'Y',
            'PASSWORD_DIGITS'      => 'Y',
            'PASSWORD_PUNCTUATION' => 'Y',
            'LOGIN_ATTEMPTS'       => '3',
        ];
        $data = ['SECURITY_POLICY' => serialize($securityPolice)];
        GroupTable::update(1, $data);

        /** включить ограничение работы во фреймах */
        \CSecurityFrame::SetActive(true);

        /** защита редиректов от фишинга */
        \CSecurityRedirect::SetActive(true);
        /** Активная реакция на вторжение
         * filter - сделать безопасными
         * clear - очистить опасные данные
         * none - оставить опасные данные как есть
         */
        Option::set('security', 'filter_action', 'clear');
        /** добавить ip адрес атакующего в стоп-лист */
        Option::set('security', 'filter_stop', 'N');
        /** на сколкьо минут добавлять в стоп-лист в минутах */
        Option::set('security', 'filter_duration', 5 * 60);
        /** заносить попытку вторжения в лог */
        Option::set('security', 'filter_log', 'Y');

        /** Включить проактивную защиту */
        \CSecurityFilter::SetActive(true);

        /** включить веб-антивирус */
        \CSecurityAntiVirus::SetActive(true);
        /** действие при обнаружении вируса replace или notify_only */
        Option::set('security', 'antivirus_action', 'replace');

        /** отключаем ошибки */
        $dbConnList = Dbconn::get();
        $dbConnList['db']['Debug'] = false;
        Dbconn::save($dbConnList);
        $val = Configuration::getValue('exception_handling');
        $val['debug'] = false;
        Configuration::setValue('exception_handling', $val);

        /** вынос временной папки за пределы док рута - папка должна существовать */
        $dbConnList = Dbconn::get();
        if (EnvType::isProd()) {
            $dbConnList['define']['custom']['BX_TEMPORARY_FILES_DIRECTORY'] = realpath($_SERVER['DOCUMENT_ROOT'] . '/../../tmp/kuhniunited.ru');
        } else {
            $dbConnList['define']['custom']['BX_TEMPORARY_FILES_DIRECTORY'] = realpath($_SERVER['DOCUMENT_ROOT'] . '/../../tmp/dev1.kuhniunited.ru');
        }
        Dbconn::save($dbConnList);

        /** хранение сессий в бд */
        \CSecuritySession::activate();
        /** смена идентификатора сессии */
        Option::set('main', 'use_session_id_ttl', 'Y');
        /** меняем каждые 5 минут */
        Option::set('main', 'session_id_ttl', 5 * 60);

        /** настрйоки меню */
        /** меню для каждого сайта свое */
        Option::set('fileman', 'different_set', 'Y');
        $this->setTypeMenu('s1', [
            'left'   => 'Левое меню',
            'top'    => 'Верхнее меню',
        ]);

        $this->setPageProp('s1', [
            'description' => 'Описание',
            'keywords'    => 'Ключевые слова',
        ]);

        return true;
    }

    public function setTypeMenu($siteId, $menuList)
    {
        $moduleId = 'fileman';
        Option::set($moduleId, 'num_menu_param', 3, false, $siteId);

        SetMenuTypes($menuList, $siteId);
    }

    public function setPageProp($siteId, $propList)
    {
        \CFileMan::SetPropstypes($propList, false, $siteId);
    }
}
