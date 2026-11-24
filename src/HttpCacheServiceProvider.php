<?php

declare(strict_types=1);

namespace CraftCms\HttpCache;

use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\HttpCache\Http\Middleware\SendTagHeader;
use CraftCms\HttpCache\Listeners\PurgeElementCaches;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Override;

class HttpCacheServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/http-cache.php', 'http-cache');

        $this->app->singletonIf(CachePolicy::class, DefaultCachePolicy::class);
        $this->app->singleton(HttpCacheManager::class, fn (Application $app) => new HttpCacheManager($app));

        $this->app->when(SendTagHeader::class)
            ->needs(TagHeaderFormatter::class)
            ->give(fn (Application $app) => $this->tagHeaderFormatter($app));
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/http-cache.php' => config_path('http-cache.php')], 'http-cache-config');

        Event::listen(ElementCachesInvalidated::class, PurgeElementCaches::class);

        // Purges are queued and sent once, after the response, command or job that queued them.
        $flush = fn () => $this->app->make(HttpCacheManager::class)->flush();
        $this->app->terminating($flush);
        // Queue workers don't terminate between jobs.
        Event::listen([JobProcessed::class, JobExceptionOccurred::class], $flush);

        /** @var HttpKernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        // First, so it sees the final response.
        $kernel->prependMiddleware(SendTagHeader::class);
    }

    private function tagHeaderFormatter(Application $app): ?TagHeaderFormatter
    {
        /** @var class-string<TagHeaderFormatter>|null $formatter */
        $formatter = config('http-cache.tags.formatter');

        return $formatter === null
            ? null
            : $app->make($formatter, config('http-cache.tags.formatter_options', []));
    }
}
