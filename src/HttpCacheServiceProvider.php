<?php

declare(strict_types=1);

namespace CraftCms\HttpCache;

use Illuminate\Support\ServiceProvider;

class HttpCacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singletonIf(CachePolicy::class, DefaultCachePolicy::class);
    }
}
