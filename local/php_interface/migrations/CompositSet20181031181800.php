<?php namespace Sprint\Migration;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Composite\Helper;
use Bitrix\Main\Config\Option;
use CGroup;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;
use Vf92\MiscUtils\EnvType;
use function defined;
use function extension_loaded;
use function in_array;
use function is_array;

class CompositSet20181031181800 extends SprintMigrationBase
{
    protected $description = 'установка композита';

    public function up()
    {
        $settings = [
            'AUTO'               => false,
            'STORAGE'            => 'files',
            //files, memcache
            'STORAGE_ADDITIONAL' => [
                'MEMCACHE_HOST' => '',
                'MEMCACHE_PORT' => '',
            ],
            'SHOW_BANNER'        => false,
            'GROUPS'             => [],
            'FRAME_MODE'         => false,//Y - голосуем за или против по умолчанию
            'FRAME_TYPE'         => 'DYNAMIC_WITH_STUB',
            //DYNAMIC_WITH_STUB - динамическая область с заглушкой - STATIC - статическая область
            'INCLUDE_MASK'       => '/*',
            'EXCLUDE_MASK'       => '/bitrix/*; /404.php; /local/*;',
            'IGNORED_PARAMETERS' => 'utm_source; utm_medium; utm_campaign; utm_content; fb_action_ids; utm_term; yclid; gclid; _openstat; from; referrer1; r1; referrer2; r2; referrer3; r3; ',
            'NO_PARAMETERS'      => '',
            //N
            'ONLY_PARAMETERS'    => 'id; ELEMENT_ID; SECTION_ID; PAGEN_1;',
            'FILE_QUOTA'         => 512,
            'BANNER_BGCOLOR'     => '#E94524',
            'BANNER_STYLE'       => 'white',
            'EXCLUDE_PARAMS'     => 'ncc;',
        ];
        if (EnvType::isProd()) {
            $settings['DOMAINS'] = ['www.kuhniunited.ru', 'kuhniunited.ru', 'count.kuhniunited.ru'];
        } else {
            $settings['DOMAINS'] = ['dev1.kuhniunited.ru'];
        }
        $this->enableComposite($settings);
    }

    public function enableComposite(array $settings)
    {
        $auto_composite = $settings['AUTO'] ? 'Y' : 'N';
        $storage = $settings['STORAGE'];
        $composite_memcached_host = $settings['STORAGE_ADDITIONAL']['MEMCACHE_HOST'];
        $composite_memcached_port = $settings['STORAGE_ADDITIONAL']['MEMCACHE_PORT'];
        $composite_show_banner = $settings['SHOW_BANNER'];
        $groups = $settings['GROUPS'];
        $domains = $settings['DOMAINS'];
        $frameMode = $settings['FRAME_MODE'] ? 'Y' : 'N';
        $frameType = $settings['FRAME_TYPE'];

        $compositeOptions['INCLUDE_MASK'] = $settings['INCLUDE_MASK'];
        $compositeOptions['EXCLUDE_MASK'] = $settings['EXCLUDE_MASK'];
        $compositeOptions['IGNORED_PARAMETERS'] = $settings['IGNORED_PARAMETERS'];
        $compositeOptions['NO_PARAMETERS'] = $settings['NO_PARAMETERS'] ? 'Y' : 'N';
        $compositeOptions['ONLY_PARAMETERS'] = $settings['ONLY_PARAMETERS'];
        $compositeOptions['FILE_QUOTA'] = $settings['FILE_QUOTA'];
        $compositeOptions['BANNER_BGCOLOR'] = $settings['BANNER_BGCOLOR'];
        $compositeOptions['BANNER_STYLE'] = $settings['BANNER_STYLE'];
        $compositeOptions['EXCLUDE_PARAMS'] = $settings['EXCLUDE_PARAMS'];

        if (($storage === 'memcached' || $storage === 'memcached_cluster') && extension_loaded('memcache')) {
            $compositeOptions['MEMCACHED_HOST'] = $composite_memcached_host;
            $compositeOptions['MEMCACHED_PORT'] = $composite_memcached_port;

            if (defined('BX_CLUSTER_GROUP')) {
                $compositeOptions['MEMCACHED_CLUSTER_GROUP'] = BX_CLUSTER_GROUP;
            }
        } else {
            $storage = 'files';
        }

        $compositeOptions['STORAGE'] = $storage;

        if ($groups !== null && is_array($groups)) {
            $compositeOptions['GROUPS'] = [];
            $b = '';
            $o = '';
            $rsGroups = CGroup::GetList($b, $o, []);
            while ($arGroup = $rsGroups->Fetch()) {
                if (($arGroup['ID'] > 2) && in_array($arGroup['ID'], $groups, true)) {
                    $compositeOptions['GROUPS'][] = $arGroup['ID'];
                }
            }
        }


        $compositeOptions['DOMAINS'] = [];
        foreach ($domains as $domain) {
            $domain = trim($domain, " \t\n\r");
            if ($domain !== '') {
                $compositeOptions['DOMAINS'][$domain] = $domain;
            }
        }

        $composite_cache_mode = 'standard_ttl';
        $composite_standard_ttl = 120;
        $composite_no_update_ttl = 600;
        if ($composite_cache_mode !== null) {
            if ($composite_cache_mode === 'standard_ttl') {
                $compositeOptions['AUTO_UPDATE'] = 'Y';
                $ttl = $composite_standard_ttl !== null ? $composite_standard_ttl : 120;
                $compositeOptions['AUTO_UPDATE_TTL'] = $ttl;
            } elseif ($composite_cache_mode === 'no_update') {
                $compositeOptions['AUTO_UPDATE'] = 'N';
                $ttl = $composite_no_update_ttl !== null ? $composite_no_update_ttl : 600;
                $compositeOptions['AUTO_UPDATE_TTL'] = $ttl;
            } else {
                $compositeOptions['AUTO_UPDATE'] = 'Y';
                $compositeOptions['AUTO_UPDATE_TTL'] = '0';
            }
        }

        $compositeOptions['FRAME_MODE'] = $frameMode;
        $compositeOptions['FRAME_TYPE'] = $frameType;

        if ($auto_composite === 'Y') {
            Helper::setEnabled(true);
            $compositeOptions['AUTO_COMPOSITE'] = 'Y';
            $compositeOptions['FRAME_MODE'] = 'Y';
            $compositeOptions['FRAME_TYPE'] = 'DYNAMIC_WITH_STUB';
            $compositeOptions['AUTO_UPDATE'] = 'Y';
            $compositeOptions['AUTO_UPDATE_TTL'] = $composite_standard_ttl ?: 120;
        } else {
            $compositeOptions['AUTO_COMPOSITE'] = 'N';
            Helper::setEnabled(true);
        }

        if ($composite_show_banner !== null && in_array($composite_show_banner, ['Y', 'N'])) {
            try {
                Option::set('main', '~show_composite_banner', $composite_show_banner);
            } catch (ArgumentOutOfRangeException $e) {
                $this->log()->error('Ошибка сохранения конфигурации - ' . $e->getMessage());
                return false;
            }
        }

        Helper::setOptions($compositeOptions);
        bx_accelerator_reset();
        return true;
    }

    public function down()
    {
        $auto_composite = 'N';//Y

        $compositeOptions = [];
        if ($auto_composite === 'Y') {
            Helper::setEnabled(false);
            $compositeOptions['AUTO_COMPOSITE'] = 'N';
            $compositeOptions['FRAME_MODE'] = 'N';
            $compositeOptions['AUTO_UPDATE_TTL'] = '0';
        } else {
            $compositeOptions['AUTO_COMPOSITE'] = 'N';
            Helper::setEnabled(false);
        }

        Helper::setOptions($compositeOptions);
        bx_accelerator_reset();
    }
}