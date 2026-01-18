<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenAI\OpenAI as OpenAIClient;

class OpenAIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('openai', function ($app) {
            return OpenAIClient::client(config('services.openai.api_key'));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
