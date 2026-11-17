<?php

declare(strict_types=1);

namespace CraftCms\HttpCache\Tests;

use CraftCms\Cms\Plugin\Testing\PluginTestCase;
use CraftCms\HttpCache\HttpCacheServiceProvider;
use Override;

abstract class TestCase extends PluginTestCase
{
    /**
     * This package isn't a plugin, so there's nothing to install.
     */
    #[Override]
    public function setupInstallsPlugin(): void {}

    #[Override]
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            HttpCacheServiceProvider::class,
        ];
    }
}
