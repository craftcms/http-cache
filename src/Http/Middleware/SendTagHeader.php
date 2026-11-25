<?php

declare(strict_types=1);

namespace CraftCms\HttpCache\Http\Middleware;

use Closure;
use CraftCms\Cms\Http\CacheInfo;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends the response's tags in the header set by `tags.formatter`, for caches that read one.
 *
 * Only successful responses to GET and HEAD requests outside the control panel and actions get the header, the same
 * responses Craft collects element tags for. Without a formatter, it does nothing.
 */
readonly class SendTagHeader
{
    public function __construct(
        private CacheInfo $cacheInfo,
        private ?TagHeaderFormatter $formatter = null,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (
            $this->formatter === null
            || ! $response instanceof Response
            || ! $response->isSuccessful()
            || ! $request->isMethodCacheable()
            || $request->isCpRequest()
            || $request->isActionRequest()
        ) {
            return $response;
        }

        $tags = $this->cacheInfo->getTags();

        if ($tags !== []) {
            $response->headers->set(
                $this->formatter->getTagsHeaderName(),
                $this->formatter->getTagsHeaderValue($tags),
                false,
            );
        }

        return $response;
    }
}
