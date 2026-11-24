# HTTP Cache for Craft CMS

Purges CDNs and caching proxies when Craft content changes, and sends response tags to caches that read them.

Craft collects the elements each response depends on with `ResponseCacheInfo`. This package does the rest:

- **Clients:** named FOSHttpCache clients (Varnish, Fastly, Cloudflare, or your own through `HttpCache::extend()`) in `config/http-cache.php`, sent through Laravel's HTTP client.
- **Tag header:** sends `ResponseCacheInfo`'s tags in the header your `tags.formatter` sets.
- **Purging:** purges element tags when Craft invalidates element caches.
- **`CachePolicy`:** decides what a cache may serve and store, for drivers.

It plays the role [FOSHttpCacheBundle](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle) plays for Symfony, built on the [FOSHttpCache](https://github.com/FriendsOfSymfony/FOSHttpCache) library.

## Installation

```sh
composer require craftcms/http-cache
php artisan vendor:publish --tag=http-cache-config
```

## Testing a plugin that uses it

Testbench doesn't run package discovery, so register the provider in your plugin's test case:

```php
protected function getPackageProviders($app): array
{
    return [...parent::getPackageProviders($app), \CraftCms\HttpCache\HttpCacheServiceProvider::class];
}
```
