<?php

declare(strict_types=1);

namespace CraftCms\HttpCache\Facades;

use CraftCms\HttpCache\HttpCacheManager;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \FOS\HttpCache\ProxyClient\ProxyClient client(?string $name = null)
 * @method static \FOS\HttpCache\CacheInvalidator invalidator(?string $name = null)
 * @method static int flush()
 * @method static HttpCacheManager extend(string $driver, \Closure $callback)
 *
 * @see HttpCacheManager
 */
class HttpCache extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return HttpCacheManager::class;
    }
}
