<?php

declare(strict_types=1);

use CraftCms\HttpCache\CacheProfile;
use CraftCms\HttpCache\DefaultCacheProfile;

it('binds the default cache profile', function () {
    expect(app(CacheProfile::class))->toBeInstanceOf(DefaultCacheProfile::class);
});
