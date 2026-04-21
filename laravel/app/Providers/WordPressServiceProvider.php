<?php

namespace App\Providers;

use App\Services\WordPressService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class WordPressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WordPressService::class, function () {
            $client = Http::baseUrl(config('services.wordpress.url'))
                ->withBasicAuth(
                    config('services.wordpress.user'),
                    config('services.wordpress.password')
                );

            return new WordPressService($client);
        });
    }
}
