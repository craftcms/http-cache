<?php

declare(strict_types=1);

use CraftCms\HttpCache\CachePolicy;
use CraftCms\HttpCache\DefaultCachePolicy;

it('binds the default cache policy', function () {
    expect(app(CachePolicy::class))->toBeInstanceOf(DefaultCachePolicy::class);
});
