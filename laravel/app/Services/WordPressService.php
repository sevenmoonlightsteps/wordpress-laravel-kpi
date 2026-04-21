<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;

class WordPressService
{
    private const POSTS_TTL = 300;
    private const PAGES_TTL = 600;
    private const POSTS_KEY_REGISTRY = 'wp_posts_keys';

    public function __construct(private readonly PendingRequest $client) {}

    public function getPosts(int $perPage = 10, int $page = 1): array
    {
        $cacheKey = "wp_posts_{$perPage}_{$page}";

        $this->registerPostKey($cacheKey);

        return Cache::remember($cacheKey, self::POSTS_TTL, function () use ($perPage, $page) {
            $response = $this->client->get('/wp-json/wp/v2/posts', [
                'per_page' => $perPage,
                'page'     => $page,
            ]);

            if ($response->failed()) {
                throw new \RuntimeException(
                    "WordPress API error fetching posts: HTTP {$response->status()}"
                );
            }

            return array_map(
                fn(array $post) => [
                    'id'      => $post['id'],
                    'title'   => $post['title']['rendered'] ?? '',
                    'slug'    => $post['slug'],
                    'excerpt' => $post['excerpt']['rendered'] ?? '',
                    'date'    => $post['date'],
                    'link'    => $post['link'],
                ],
                $response->json()
            );
        });
    }

    public function getPages(): array
    {
        return Cache::remember('wp_pages', self::PAGES_TTL, function () {
            $response = $this->client->get('/wp-json/wp/v2/pages');

            if ($response->failed()) {
                throw new \RuntimeException(
                    "WordPress API error fetching pages: HTTP {$response->status()}"
                );
            }

            return array_map(
                fn(array $page) => [
                    'id'    => $page['id'],
                    'title' => $page['title']['rendered'] ?? '',
                    'slug'  => $page['slug'],
                    'date'  => $page['date'],
                    'link'  => $page['link'],
                ],
                $response->json()
            );
        });
    }

    public function flushCache(): void
    {
        $postKeys = Cache::get(self::POSTS_KEY_REGISTRY, []);

        foreach ($postKeys as $key) {
            Cache::forget($key);
        }

        Cache::forget(self::POSTS_KEY_REGISTRY);
        Cache::forget('wp_pages');
    }

    private function registerPostKey(string $key): void
    {
        $keys = Cache::get(self::POSTS_KEY_REGISTRY, []);

        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::put(self::POSTS_KEY_REGISTRY, $keys, self::POSTS_TTL);
        }
    }
}
