<?php namespace Sprint\Migration;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Localization\CultureTable;
use Exception;
use Vf92\BitrixUtils\BitrixUtils;
use Vf92\BitrixUtils\Config\Dbconn;
use Vf92\BitrixUtils\Migration\SprintMigrationBase;

class ConvertToUtf820180926160900 extends SprintMigrationBase
{
    protected $description = '';

    public function up()
    {
        /** @todo првоерка на mbstring.func_overload 2 и mbstring.internal_encoding utf8 */
        ob_start();
        phpinfo();
        $info = ob_get_clean();
        preg_match_all('/<tr><td\sclass=[\'"]e[\'"]\s*>(mbstring\.(.((?!<\/).)*))<\/td><td\sclass=[\'"]v[\'"]\s*><i>(.*)<\/i><\/td><td\sclass=[\'"]v[\'"]\s*><i>(.*)<\/i><\/td>/im',
            $info, $matches);
        var_dump($matches);
        echo $info;

        $dbConnList = Dbconn::get();
        $config = Configuration::getInstance();
        $configUtf8 = $config->get('utf_mode');
        if (!$configUtf8 || !$dbConnList['define']['project']['BX_UTF']) {
            /** правка настроек */
            $dbConnList['define']['project']['BX_UTF'] = true;
            Dbconn::save($dbConnList);

            $config->addReadonly('utf_mode', true);
            try {
                $config->saveConfiguration();
            } catch (InvalidOperationException $e) {
                $this->log()->error('Ошибка сохранения конфигурации - ' . $e->getMessage());
                return false;
            }

            try {
                $res = CultureTable::query()->whereNot('CHARSET', 'UTF-8')->setSelect(['ID'])->exec();
                while ($item = $res->fetch()) {
                    try {
                        $res = CultureTable::update((int)$item['ID'], ['CHARSET' => 'UTF-8']);
                        if (!$res->isSuccess()) {
                            $this->log()->error(BitrixUtils::extractErrorMessage($res));
                        }
                    } catch (Exception $e) {
                        $this->log()->error('Ошибка сохранения culture - ' . $e->getMessage());
                        return false;
                    }
                }
            } catch (Exception $e) {
                $this->log()->error('Ошибка query - ' . $e->getMessage());
                return false;
            }

            /** переконвертация файлов */
            $res = shell_exec('cd ' . $_SERVER['DOCUMENT_ROOT'] . ';FILES=./*.php
                for i in $FILES; do
                    mv $i $i.icv
                    iconv -f WINDOWS-1251 -t UTF-8 $i.icv > $i
                    rm -f $i.icv
                done
                echo "OK"'
            );
            if ($res === 'OK') {
                /**  переконвертация базы */
                $connection = Application::getConnection();
                $charset = 'utf8';
                $collation = 'utf8_general_ci';
                $baseSql = 'SELECT CONCAT(  "ALTER TABLE `", t.`TABLE_SCHEMA` ,  "`.`", t.`TABLE_NAME` ,  "` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collation . ';" ) AS sqlcode
FROM  `information_schema`.`TABLES` t
WHERE t.`TABLE_COLLATION` <> "' . $collation . '"
AND t.`TABLE_SCHEMA` =  "' . $connection->getDatabase() . '"
ORDER BY t.`TABLE_NAME` ASC 
LIMIT 1;
';
                try {
                    $sql = $connection->query($baseSql)->fetch();
                    while (true) {
                        if (!empty($sql)) {
                            $sql = $connection->query($sql . $baseSql)->fetch();
                        } else {
                            break;
                        }
                    }
                } catch (SqlQueryException $e) {
                    $this->log()->error('Ошибка sql - ' . $e->getMessage());
                    return false;
                }
            }

            /** @todo правка autoconnect */
            /** @todo проверить */
        }
        return true;
    }

    public function down()
    {
    }
}