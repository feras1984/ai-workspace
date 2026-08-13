<?php

namespace App\Providers;

use App\Contracts\AI\EmbeddingDriverInterface;
use App\Contracts\AI\LLMDriverInterface;
use App\Contracts\AI\VectorStoreInterface;
use App\Services\AI\Ollama\OllamaEmbeddingDriver;
use App\Services\AI\Ollama\OllamaLLMDriver;
use App\Services\AI\PgVector\PgVectorStore;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EmbeddingDriverInterface::class, OllamaEmbeddingDriver::class);
        $this->app->singleton(LLMDriverInterface::class, OllamaLLMDriver::class);
        $this->app->singleton(VectorStoreInterface::class, PgVectorStore::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
