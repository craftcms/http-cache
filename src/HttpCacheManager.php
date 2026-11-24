<?php

declare(strict_types=1);

namespace CraftCms\HttpCache;

use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\ProxyClient\Cloudflare;
use FOS\HttpCache\ProxyClient\Fastly;
use FOS\HttpCache\ProxyClient\Noop;
use FOS\HttpCache\ProxyClient\ProxyClient;
use FOS\HttpCache\ProxyClient\Varnish;
use Illuminate\Support\Arr;
use Illuminate\Support\MultipleInstanceManager;

/**
 * Builds the FOSHttpCache clients named in `config/http-cache.php`.
 *
 * Each client's config has a `driver`, optional `servers` and `base_uri` for the dispatcher, and the FOSHttpCache
 * client's own options. Register other drivers with `extend()`:
 *
 * ```php
 * HttpCache::extend('cloud', fn (Application $app, array $config) => new CloudClient(...));
 * ```
 */
class HttpCacheManager extends MultipleInstanceManager
{
    /**
     * @var array<string, CacheInvalidator>
     */
    private array $invalidators = [];

    public function client(?string $name = null): ProxyClient
    {
        return $this->instance($name);
    }

    /**
     * The invalidator for a client, which queues invalidations until they're sent.
     */
    public function invalidator(?string $name = null): CacheInvalidator
    {
        $name ??= $this->getDefaultInstance();

        return $this->invalidators[$name] ??= new CacheInvalidator($this->client($name));
    }

    /**
     * Sends every client's queued invalidations. A failed client is reported, not thrown, so one unreachable cache
     * doesn't stop the others or fail the request, job or command that queued them.
     *
     * @return int The number of invalidation requests sent.
     */
    public function sendInvalidations(): int
    {
        $sent = 0;

        foreach ($this->invalidators as $invalidator) {
            try {
                $sent += $invalidator->flush();
            } catch (ExceptionCollection $exceptions) {
                foreach ($exceptions as $exception) {
                    report($exception);
                }
            }
        }

        return $sent;
    }

    public function getDefaultInstance(): string
    {
        return $this->config->get('http-cache.default');
    }

    public function setDefaultInstance($name): void
    {
        $this->config->set('http-cache.default', $name);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getInstanceConfig($name): ?array
    {
        $config = $this->config->get("http-cache.clients.$name");

        // A driver registered with `extend()` under the client's name needs no `driver` key, or no entry at all.
        if (isset($this->customCreators[$name])) {
            return ['driver' => $name, ...($config ?? [])];
        }

        return $config;
    }

    protected function createNoopDriver(): Noop
    {
        return new Noop;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function createVarnishDriver(array $config): Varnish
    {
        return new Varnish($this->dispatcher($config), $this->options($config));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function createFastlyDriver(array $config): Fastly
    {
        return new Fastly($this->dispatcher($config, ['https://api.fastly.com']), $this->options($config));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function createCloudflareDriver(array $config): Cloudflare
    {
        return new Cloudflare($this->dispatcher($config, ['https://api.cloudflare.com']), $this->options($config));
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  list<string>  $servers
     */
    protected function dispatcher(array $config, array $servers = []): LaravelHttpDispatcher
    {
        return new LaravelHttpDispatcher($config['servers'] ?? $servers, $config['base_uri'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function options(array $config): array
    {
        return Arr::except($config, ['driver', 'servers', 'base_uri']);
    }
}
