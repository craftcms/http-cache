# HTTP Cache for Craft CMS

Response tags, cache rules, and CDN invalidation for Craft CMS 6, built on [FOSHttpCache](https://github.com/FriendsOfSymfony/FOSHttpCache).

Work in progress. The design is in the Craft 6 HTTP cache design document.

## Requirements

Craft CMS 6.0 or later, and PHP 8.5 or later.

## Cache policy

`CraftCms\HttpCache\CachePolicy` decides what a cache may serve and store:

- `isRequestCacheable()`: whether a request may be answered from the cache.
- `isResponseCacheable()`: whether a finished response may be stored.

The default, `DefaultCachePolicy`, serves every GET and HEAD request from the cache, with or without cookies. It stores only responses that Symfony's `Response::isCacheable()` accepts and that don't set cookies. To change the rules, bind your own implementation:

```php
$this->app->singleton(CachePolicy::class, MyCachePolicy::class);
```

## Development

```bash
composer install
composer ci
```
