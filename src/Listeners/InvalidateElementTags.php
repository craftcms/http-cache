<?php

declare(strict_types=1);

namespace CraftCms\HttpCache\Listeners;

use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\HttpCache\HttpCacheManager;
use FOS\HttpCache\CacheInvalidator;

/**
 * Invalidates the cached pages tagged with the elements whose caches Craft invalidated, on every client that can
 * invalidate by tag.
 *
 * The tags are the same ones Craft collects from element queries, so a page is invalidated when an element it showed,
 * or could have shown, changes. Drafts and revisions are skipped, because visitors never see them. Clients that can't
 * invalidate by tag are skipped too; a driver without tags handles element changes itself.
 */
readonly class InvalidateElementTags
{
    public function __construct(
        private HttpCacheManager $httpCache,
    ) {}

    public function handle(ElementCachesInvalidated $event): void
    {
        if ($event->element !== null && ElementHelper::isDraftOrRevision($event->element)) {
            return;
        }

        if ($event->tags === []) {
            return;
        }

        // Clients are named by their config keys, so a numeric key isn't a client.
        foreach (array_filter(array_keys(config()->array('http-cache.clients', [])), is_string(...)) as $name) {
            $invalidator = $this->httpCache->invalidator($name);

            if ($invalidator->supports(CacheInvalidator::TAGS)) {
                $invalidator->invalidateTags(array_values($event->tags));
            }
        }
    }
}
