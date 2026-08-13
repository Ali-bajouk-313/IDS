<?php

namespace App\Providers;

use App\Services\Ai\AiProviderFactory;
use App\Services\Ai\AiService;
use App\Services\Ai\ChatbotAssistantService;
use App\Services\Ai\KnowledgeBaseAssistantService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AiProviderFactory::class);
        $this->app->singleton(AiService::class);
        $this->app->singleton(KnowledgeBaseAssistantService::class);
        $this->app->singleton(ChatbotAssistantService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
