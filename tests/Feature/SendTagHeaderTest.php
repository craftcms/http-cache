<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\ResponseTagger;
use FOS\HttpCache\TagHeaderFormatter\CommaSeparatedTagHeaderFormatter;
use Illuminate\Support\Facades\Route;

use function CraftCms\Cms\renderString;

beforeEach(function () {
    config([
        'http-cache.tags.formatter' => CommaSeparatedTagHeaderFormatter::class,
        'http-cache.tags.formatter_options' => ['headerName' => 'Cache-Tag'],
    ]);
});

it('sends element tags and tags added in PHP and Twig', function () {
    Route::get('/page', function () {
        Entry::find()->id(123)->one();
        ResponseTagger::addTags(['from-php']);

        return response(renderString("{% do ResponseTagger.addTags(['from-twig']) %}ok"));
    });

    $tags = explode(',', (string) $this->get('/page')->headers->get('Cache-Tag'));

    expect($tags)->toContain('element::'.Entry::class, 'element::123', 'from-php', 'from-twig');
});

it('uses the configured formatter', function () {
    config(['http-cache.tags.formatter_options' => ['headerName' => 'Surrogate-Key', 'glue' => ' ']]);

    Route::get('/page', function () {
        ResponseTagger::addTags(['a', 'b']);

        return response('ok', headers: ['Surrogate-Key' => 'existing']);
    });

    expect($this->get('/page')->headers->all('surrogate-key'))->toBe(['existing', 'a b']);
});

it('only tags eligible responses', function (string $method, string $uri, int $status) {
    $uri = $uri === 'cp' ? '/'.Cms::config()->cpTrigger.'/page' : $uri;

    Route::match(['GET', 'HEAD', 'POST'], $uri, function () use ($status) {
        ResponseTagger::addTags(['page']);

        return response('ok', $status);
    });

    expect($this->call($method, $uri)->headers->has('Cache-Tag'))->toBeFalse();
})->with([
    'POST' => ['POST', '/page', 200],
    'control panel' => ['GET', 'cp', 200],
    'action' => ['GET', '/actions/foo/bar', 200],
    'not found' => ['GET', '/page', 404],
    'server error' => ['GET', '/page', 500],
]);

it('sends no tag header without a formatter', function () {
    config(['http-cache.tags.formatter' => null]);

    Route::get('/page', function () {
        ResponseTagger::addTags(['page']);

        return response('ok');
    });

    expect($this->get('/page')->headers->has('Cache-Tag'))->toBeFalse()
        ->and(ResponseTagger::getTags())->toBe(['page']);
});
