<?php

namespace Sprint\Migration;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;
use Bitrix\Main\GroupTable;
use Bitrix\Main\Localization\CultureTable;
use Bitrix\Main\SiteDomainTable;
use Bitrix\Main\SiteTable;
use Bitrix\Main\SiteTemplateTable;
use Bitrix\Main\SystemException;
use CFileMan;
use CSecurityAntiVirus;
use CSecurityFilter;
use CSecurityFrame;
use CSecurityRedirect;
use CSecuritySession;
use Exception;
use Vf92\BitrixUtils\BitrixUtils;
use Vf92\BitrixUtils\Config\Dbconn;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;
use Vf92\MiscUtils\EnvType;
use function in_array;

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
        /** устанавливать ли в главном модуле email, site_name, server_name */
        $setMainModuleSiteSettings = true;
//        $email = 'info@' . $serverName;
        $email = 'email';
        $templates = [
            [
                'SITE_ID'   => $siteId,
                'CONDITION' => '',
                'SORT'      => 1,
                'TEMPLATE'  => 'main',
            ],
        ];
        if (EnvType::isProd()) {
            $projectName = 'project.ru';
        } else {
            $projectName = 'dev.project.ru';
        }

        //Установка настроек сайта
        try {
            $lang = Application::getInstance()->getContext()->getLanguage();
        } catch (SystemException $e) {
            $this->log()->error('Ошибка получения контекста ' . $e->getMessage());
            return false;
        }
        if (empty($lang)) {
            $lang = 'ru';
        }
        $culture = null;
        try {
            $culture = CultureTable::query()
                ->setSelect(['ID'])->where('CODE', $lang)
//                ->fetchObject();
                ->fetch();
        } catch (Exception $e) {
            $this->log()->error('Ошибка query|fetch ' . $e->getMessage());
            return false;
        }
        if ($culture === null) {
            $cultureId = 1;
        } else {
//            $cultureId = $culture->getId();
            $cultureId = $culture['ID'];
        }
        try {
            $res = SiteTable::update($siteId, [
                'NAME'        => $siteName,
                'SITE_NAME'   => $siteNameFull,
                'SERVER_NAME' => $serverName,
                'EMAIL'       => $email,
                'LANGUAGE_ID' => $lang,
                'CULTURE_ID'  => $cultureId,
//            'DEF'            => 'N',
//            'SORT'           => '20',
//            'ACTIVE'         => 'Y',
//            'DIR'            => '/',
//            'DOC_ROOT'       => $_SERVER['DOCUMENT_ROOT'],
            ]);
            if ($res->isSuccess()) {
                $this->log()->info('Настрйоки сайта успешно изменены');
            } else {
                $this->log()->error('Ошибка сохранения настреок сайта: ' . implode('; ', $res->getErrorMessages()));
                return false;
            }
        } catch (Exception $e) {
            $this->log()->error('Ошибка сохранения настроек сайта: ' . $e->getMessage());
            return false;
        }

        //Установка доменов сайта
        try {
            $res = $currentSites = SiteDomainTable::query()->setSelect(['DOMAIN'])
                ->where('LID', $siteId)
                ->exec();
        } catch (Exception $e) {
            $this->log()->error('Ошибка query ' . $e->getMessage());
            return false;
        }
        $currentSites = [];
        try {
            while ($item = $res->fetch()) {
//            while ($item = $res->fetchObject()) {
//                $currentSites[] = $item->getDomain();
                $currentSites[] = $item['DOMAIN'];
            }
        } catch (Exception $e) {
            $this->log()->error('Ошибка fetch ' . $e->getMessage());
            return false;
        }
        $addSites = [
            $serverName,
            'www.' . $serverName,
        ];
        foreach ($addSites as $addSite) {
            if (!in_array($addSite, $currentSites, true)) {
                try {
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
                } catch (Exception $e) {
                    $this->log()->error('Ошибка при добавлении домена ' . $addSite . ' - ' . $e->getMessage());
                    return false;
                }
            }
        }

        //Установка шаблонов сайта
        if (!empty($templates)) {
            foreach ($templates as $template) {
                try {
                    $res = SiteTemplateTable::add($template);
                    if (!$res->isSuccess()) {
                        $this->log()->error('Шаблоны сайта установить не получилось ' . $template['TEMPLATE']);
                        return false;
                    }
                } catch (Exception $e) {
                    $this->log()->error('Шаблоны сайта установить не получилось ' . $template['TEMPLATE'] . ' - ' . $e->getMessage());
                    return false;
                }
            }
        }

        if ($setMainModuleSiteSettings) {
            try {
                Option::set('main', 'site_name', $siteNameFull);
                Option::set('main', 'server_name', $serverName);
                Option::set('main', 'email_from', $email);
            } catch (ArgumentOutOfRangeException $e) {
                $this->log()->error('Ошибка сохранения конфигурации - ' . $e->getMessage());
                return false;
            }
        }
        try {
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

        } catch (ArgumentOutOfRangeException $e) {
            $this->log()->error('Ошибка сохранения конфигурации - ' . $e->getMessage());
            return false;
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

        try {
            $res = GroupTable::update(1, $data);
            if (!$res->isSuccess()) {
                $this->log()->error('Ошибка обновления админской группы - ' . BitrixUtils::extractErrorMessage($res));
                return false;
            }
        } catch (Exception $e) {
            $this->log()->error('Ошибка обновления админской группы - ' . $e->getMessage());
            return false;
        }

        /** включить ограничение работы во фреймах */
        CSecurityFrame::SetActive(true);

        /** защита редиректов от фишинга */
        CSecurityRedirect::SetActive(true);
        /** Активная реакция на вторжение
         * filter - сделать безопасными
         * clear - очистить опасные данные
         * none - оставить опасные данные как есть
         */
        try {
            Option::set('security', 'filter_action', 'clear');
            /** добавить ip адрес атакующего в стоп-лист */
            Option::set('security', 'filter_stop', 'N');
            /** на сколкьо минут добавлять в стоп-лист в минутах */
            Option::set('security', 'filter_duration', 5 * 60);
            /** заносить попытку вторжения в лог */
            Option::set('security', 'filter_log', 'Y');
        } catch (ArgumentOutOfRangeException $e) {
            $this->log()->error('Ошибка сохранения конфигурации - ' . $e->getMessage());
            return false;
        }
        /** Включить проактивную защиту */
        CSecurityFilter::SetActive(true);

        /** включить веб-антивирус */
        CSecurityAntiVirus::SetActive(true);
        /** действие при обнаружении вируса replace или notify_only */
        try {
            Option::set('security', 'antivirus_action', 'replace');
        } catch (ArgumentOutOfRangeException $e) {
            $this->log()->error('Ошибка сохранения конфигурации - ' . $e->getMessage());
            return false;
        }

        /** отключаем ошибки */
        $dbConnList = Dbconn::get();
        $dbConnList['db']['Debug'] = false;
        Dbconn::save($dbConnList);
        $val = Configuration::getValue('exception_handling');
        $val['debug'] = false;
        Configuration::setValue('exception_handling', $val);

        /** вынос временной папки за пределы док рута - папка должна существовать */
        $dbConnList = Dbconn::get();
        $dbConnList['define']['custom']['BX_TEMPORARY_FILES_DIRECTORY'] = realpath($_SERVER['DOCUMENT_ROOT'] . '/../../tmp/' . $projectName);

        Dbconn::save($dbConnList);

        /** хранение сессий в бд */
        CSecuritySession::activate();
        /** смена идентификатора сессии */
        try {
            Option::set('main', 'use_session_id_ttl', 'Y');
            /** меняем каждые 5 минут */
            Option::set('main', 'session_id_ttl', 5 * 60);
        } catch (ArgumentOutOfRangeException $e) {
            $this->log()->error('Ошибка сохранения конфигурации - ' . $e->getMessage());
            return false;
        }

        /** настрйоки меню */
        /** меню для каждого сайта свое */
        try {
            Option::set('fileman', 'different_set', 'Y');
        } catch (ArgumentOutOfRangeException $e) {
            $this->log()->error('Ошибка сохранения конфигурации - ' . $e->getMessage());
            return false;
        }
        $this->setTypeMenu('s1', [
            'left' => 'Левое меню',
            'top'  => 'Верхнее меню',
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
        try {
            Option::set($moduleId, 'num_menu_param', 3, $siteId);
        } catch (ArgumentOutOfRangeException $e) {
            $this->log()->error('Ошибка сохранения конфигурации - ' . $e->getMessage());
            return false;
        }

        SetMenuTypes($menuList, $siteId);
        return true;
    }

    public function setPageProp($siteId, $propList)
    {
        CFileMan::SetPropstypes($propList, false, $siteId);
    }
}
