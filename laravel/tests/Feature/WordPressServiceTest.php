<?php

use App\Services\WordPressService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// ---------------------------------------------------------------------------
// Helper to build a WordPressService with a base URL pointing at the fake
// ---------------------------------------------------------------------------

function makeWpService(): WordPressService
{
    return new WordPressService(
        Http::baseUrl('http://wordpress.test')
    );
}

// ---------------------------------------------------------------------------
// Sample API payloads
// ---------------------------------------------------------------------------

function samplePosts(): array
{
    return [
        [
            'id'      => 1,
            'title'   => ['rendered' => 'Hello World'],
            'slug'    => 'hello-world',
            'excerpt' => ['rendered' => '<p>Excerpt</p>'],
            'date'    => '2026-01-01T00:00:00',
            'link'    => 'http://example.com/hello-world',
        ],
    ];
}

function samplePages(): array
{
    return [
        [
            'id'    => 10,
            'title' => ['rendered' => 'About'],
            'slug'  => 'about',
            'date'  => '2026-01-02T00:00:00',
            'link'  => 'http://example.com/about',
        ],
    ];
}

// ---------------------------------------------------------------------------
// getPosts()
// ---------------------------------------------------------------------------

it('getPosts returns simplified post objects on success', function () {
    Http::fake([
        'http://wordpress.test/wp-json/wp/v2/posts*' => Http::response(samplePosts(), 200),
    ]);

    $service = makeWpService();
    $posts   = $service->getPosts();

    expect($posts)->toHaveCount(1)
        ->and($posts[0])->toMatchArray([
            'id'    => 1,
            'title' => 'Hello World',
            'slug'  => 'hello-world',
            'link'  => 'http://example.com/hello-world',
        ]);
});

it('getPosts throws RuntimeException on HTTP 500', function () {
    Http::fake([
        'http://wordpress.test/wp-json/wp/v2/posts*' => Http::response([], 500),
    ]);

    $service = makeWpService();
    $service->getPosts();
})->throws(\RuntimeException::class);

it('getPosts returns cached result on second call', function () {
    Http::fake([
        'http://wordpress.test/wp-json/wp/v2/posts*' => Http::response(samplePosts(), 200),
    ]);

    $service = makeWpService();
    $service->getPosts();
    $service->getPosts(); // second call should hit cache

    Http::assertSentCount(1);
});

// ---------------------------------------------------------------------------
// getPages()
// ---------------------------------------------------------------------------

it('getPages returns simplified page objects on success', function () {
    Http::fake([
        'http://wordpress.test/wp-json/wp/v2/pages' => Http::response(samplePages(), 200),
    ]);

    $service = makeWpService();
    $pages   = $service->getPages();

    expect($pages)->toHaveCount(1)
        ->and($pages[0])->toMatchArray([
            'id'   => 10,
            'title' => 'About',
            'slug'  => 'about',
        ]);
});

it('getPages throws RuntimeException on HTTP error', function () {
    Http::fake([
        'http://wordpress.test/wp-json/wp/v2/pages' => Http::response([], 503),
    ]);

    $service = makeWpService();
    $service->getPages();
})->throws(\RuntimeException::class);

// ---------------------------------------------------------------------------
// flushCache()
// ---------------------------------------------------------------------------

it('flushCache clears cached posts and pages so next call hits HTTP', function () {
    Http::fake([
        'http://wordpress.test/wp-json/wp/v2/posts*' => Http::response(samplePosts(), 200),
        'http://wordpress.test/wp-json/wp/v2/pages'  => Http::response(samplePages(), 200),
    ]);

    $service = makeWpService();

    // Populate cache
    $service->getPosts();
    $service->getPages();

    Http::assertSentCount(2);

    // Flush cache
    $service->flushCache();

    // Next calls must reach HTTP again
    $service->getPosts();
    $service->getPages();

    Http::assertSentCount(4);
});
