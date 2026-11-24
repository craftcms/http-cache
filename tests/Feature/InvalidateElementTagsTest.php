<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\HttpCache\Facades\HttpCache;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'http-cache.default' => 'cloudflare',
        'http-cache.clients.cloudflare' => [
            'driver' => 'cloudflare',
            'authentication_token' => 'secret',
            'zone_identifier' => 'zone',
        ],
    ]);
});

function purgedTags(): array
{
    return Http::recorded()->flatMap(fn (array $pair) => $pair[0]->data()['tags'] ?? [])->all();
}

it('purges invalidated element tags once the request ends', function () {
    Http::fake();
    event(new ElementCachesInvalidated(['element::5', 'element::'.Entry::class.'::*'], new Entry(['id' => 5])));

    Http::assertNothingSent();

    app()->terminate();

    expect(purgedTags())->toBe(['element::5', 'element::'.Entry::class.'::*']);
});

it('purges after each queued job', function () {
    Http::fake();
    config(['queue.default' => 'sync']);

    dispatch(fn () => event(new ElementCachesInvalidated(['element::5'])));

    expect(purgedTags())->toContain('element::5');
});

it('skips drafts and revisions', function (array $attributes) {
    Http::fake();
    event(new ElementCachesInvalidated(['element::5'], new Entry(['id' => 5, ...$attributes])));

    app()->terminate();

    Http::assertNothingSent();
})->with([
    'draft' => [['draftId' => 3]],
    'revision' => [['revisionId' => 3]],
]);

it('reports a failed purge instead of throwing', function () {
    Http::fake(['*' => Http::response(status: 503)]);
    $reported = [];
    app(ExceptionHandler::class)->reportable(function (Throwable $e) use (&$reported) {
        $reported[] = $e;
    });

    event(new ElementCachesInvalidated(['element::5']));

    expect(HttpCache::sendInvalidations())->toBe(0)
        ->and($reported)->not->toBeEmpty();
});
