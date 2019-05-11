<?php namespace ProjectName\Composer;

use Composer\Config;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Script\Event;

class PostComposerUpdate
{
    public static function updateWebarchitect609BitrixCacheOnPachage(PackageEvent $event)
    {
        if (PHP_MAJOR_VERSION < 7) {
            /** @var InstallOperation|UpdateOperation $operation */
            $operation = $event->getOperation();
            $package = method_exists($operation, 'getPackage')
                ? $operation->getPackage()
                : $operation->getInitialPackage();
            if ($package->getName() === 'webarchitect609/bitrix-cache') {
                /** @var Config $config */
                $config = $event->getComposer()->getConfig();
                $vendorDir = $config->get('vendor-dir');
                $filePath = $vendorDir.'/webarchitect609/bitrix-cache/src/main/BitrixCache.php';
                if (file_exists($filePath)) {
                    $data = file_get_contents($filePath);
                    $data = str_replace('$result = ($this->callback)();',
                        'if(\is_callable($this->callback)) {$result = call_user_func($this->callback);}', $data);
                    file_put_contents($filePath, $data);
                }
            }
        }
    }

    public static function updateWebarchitect609BitrixCache(Event $event)
    {
        if (PHP_MAJOR_VERSION < 7) {
            /** @var Config $config */
            $config = $event->getComposer()->getConfig();
            $vendorDir = $config->get('vendor-dir');
            $filePath = $vendorDir . '/webarchitect609/bitrix-cache/src/main/BitrixCache.php';
            if (file_exists($filePath)) {
                $data = file_get_contents($filePath);
                $data = str_replace('$result = ($this->callback)();',
                    'if(\is_callable($this->callback)) {$result = call_user_func($this->callback);}', $data);
                file_put_contents($filePath, $data);
            }
        }
    }
}