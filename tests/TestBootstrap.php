<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$projectRoot = __DIR__;
while ($projectRoot !== '/' && !file_exists($projectRoot . '/vendor/autoload.php')) {
    $projectRoot = dirname($projectRoot);
}

if (!file_exists($projectRoot . '/vendor/shopware/core/TestBootstrapper.php')) {
    require_once $projectRoot . '/vendor/autoload.php';

    // Fallback for unit-only runs without full Shopware bootstrap
    return;
}

require_once $projectRoot . '/vendor/shopware/core/TestBootstrapper.php';

(new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('SwpMemoryProfiler')
    ->setForceInstallPlugins(true)
    ->bootstrap();
