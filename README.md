# HTTP Cache for Craft CMS

Purges CDNs and caching proxies when Craft content changes, and sends response tags to caches that read them.

Craft collects the elements each response depends on with `CacheInfo`. This package does the rest:

- **Clients:** named FOSHttpCache clients (Varnish, Fastly, Cloudflare, or your own through `HttpCache::extend()`) in `config/http-cache.php`, sent through Laravel's HTTP client.
- **Tag header:** sends `CacheInfo`'s tags in the header your `tags.formatter` sets.
- **Invalidation:** invalidates tagged pages on every client that supports tags when Craft invalidates cache tags, such as when an element is saved.
- **`CacheProfile`:** decides what a cache may serve and store, for drivers.

It plays the role [FOSHttpCacheBundle](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle) plays for Symfony, built on the [FOSHttpCache](https://github.com/FriendsOfSymfony/FOSHttpCache) library.

## Installation

```sh
composer require craftcms/http-cache
php artisan vendor:publish --tag=http-cache-config
```

## Drivers

A driver, such as a CDN integration or a static cache, builds on these:

- `HttpCache::invalidator()` queues invalidations by tag or URL for a client; `HttpCache::extend()` registers a driver. Queued invalidations are sent once with `HttpCache::sendInvalidations()`, after each request, console command and queued job.
- When Craft invalidates cache tags, such as an element's tags when it's saved or `nav` with `ElementCaches::invalidateTags()`, the tags go to the default client and every client in `http-cache.clients` that can invalidate by tag. Any other client registered with `extend()` needs an entry there to receive them, such as `'static-cache' => []`.
- `CacheProfile`, bound to `DefaultCacheProfile`, decides whether a request may be served from a cache (`isRequestCacheable()`) and whether a response may be stored (`isResponseCacheable()`). Check `isResponseCacheable()` before adding your own shared-cache header, such as `CDN-Cache-Control`, so `no-store`, `no-cache` and `private` responses don't get one.
- `CacheInfo::getTags()` and `getExpiryDate()`, from Craft, give the response's tags and the earliest expiry of its elements, for drivers that keep their own index or lifetime.

## Testing a plugin that uses it

Testbench doesn't run package discovery, so register the provider in your plugin's test case:

```php
protected function getPackageProviders($app): array
{
    return [...parent::getPackageProviders($app), \CraftCms\HttpCache\HttpCacheServiceProvider::class];
}
```

Invalidations queued while setting up a test, such as by saving entries, are sent when the next request in the test terminates, which can clear a page that request just cached. Call `HttpCache::sendInvalidations()` at the end of your setup.
