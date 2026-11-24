<?php

declare(strict_types=1);

namespace CraftCms\HttpCache\Listeners;

use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\HttpCache\HttpCacheManager;
use FOS\HttpCache\CacheInvalidator;

/**
 * Purges the default client's cached pages tagged with the elements whose caches Craft invalidated.
 *
 * The tags are the same ones Craft collects from element queries, so a page is purged when an element it showed, or
 * could have shown, changes. Drafts and revisions are skipped, because visitors never see them. Clients that can't
 * purge by tag are skipped too; a driver without tags handles element changes itself.
 */
readonly class PurgeElementCaches
{
    public function __construct(
        private HttpCacheManager $httpCache,
    ) {}

    public function handle(ElementCachesInvalidated $event): void
    {
        if ($event->element !== null && ElementHelper::isDraftOrRevision($event->element)) {
            return;
        }

        $invalidator = $this->httpCache->invalidator();

        if ($event->tags !== [] && $invalidator->supports(CacheInvalidator::TAGS)) {
            $invalidator->invalidateTags(array_values($event->tags));
        }
    }
}
