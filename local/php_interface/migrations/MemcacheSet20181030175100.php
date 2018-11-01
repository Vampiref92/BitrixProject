<?php namespace Sprint\Migration;

use Bitrix\Main\Config\Configuration;
use Vf92\BitrixUtils\Config\Dbconn;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;
use Vf92\MiscUtils\EnvType;

class MemcacheSet20181030175100 extends SprintMigrationBase
{
    protected $description = 'Установка memcache';

    public function up()
    {
        $type = 'memcache';
        if (EnvType::isProd()) {
            $postfix = 'kuhniunited';
        } elseif (EnvType::isDev()) {
            $postfix = 'dev_kuhniunited';
        } else {
            $postfix = 'custom_kuhniunited';
        }
        $sid = $_SERVER['DOCUMENT_ROOT'] . '#' . $postfix;
        $host = '';
        $port = 11211;
        if ($type === 'memcache') {
            $host = 'unix:///tmp/memcached.sock';
            $port = 0;
        }
        $dbConnList = Dbconn::get();
        $dbConnList['define']['cache'] = [
            'BX_CACHE_TYPE' => $type,
            'BX_CACHE_SID'  => $sid,
        ];
        if ($type === 'memcache') {
            $dbConnList['define']['cache'] = array_merge($dbConnList['define']['cache'], [
                'BX_MEMCACHE_HOST' => $host,
                'BX_MEMCACHE_PORT' => $port,
            ]);
        }
        Dbconn::save($dbConnList);

        $cacheVal = [
            'type' => $type,
            'sid'  => $sid,
        ];
        if ($type === 'memcache') {
            $cacheVal = array_merge($cacheVal, [
                'memcache' => [
                    'host' => $host,
                    'port' => $port,
                ],
            ]);
        }
        Configuration::setValue('cache', $cacheVal);
    }

    public function down()
    {
        $type = 'files';
        if (EnvType::isProd()) {
            $postfix = 'kuhniunited';
        } elseif (EnvType::isDev()) {
            $postfix = 'dev_kuhniunited';
        } else {
            $postfix = 'custom_kuhniunited';
        }
        $sid = $_SERVER['DOCUMENT_ROOT'] . '#' . $postfix;

        $dbConnList = Dbconn::get();
        $dbConnList['define']['cache'] = [
            'BX_CACHE_TYPE' => $type,
            'BX_CACHE_SID'  => $sid,
        ];
        Dbconn::save($dbConnList);

        $cacheVal = [
            'type' => $type,
            'sid'  => $sid,
        ];
        Configuration::setValue('cache', $cacheVal);
    }
}