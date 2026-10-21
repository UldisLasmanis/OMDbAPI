<?php

namespace App\Providers;

use App\Services\OmdbClient;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            OmdbClient::class,
            function ($app): OmdbClient {
                $apiKey = config('services.omdb.key');

                if (!is_string($apiKey) || $apiKey === '') {
                    throw new RuntimeException('OMDB_API_KEY is not configured.');
                }

                return new OmdbClient(
                    http: $app->make(Factory::class),
                    apiKey: $apiKey,
                    baseUrl: (string) config('services.omdb.base_url'),
                );
            },
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
