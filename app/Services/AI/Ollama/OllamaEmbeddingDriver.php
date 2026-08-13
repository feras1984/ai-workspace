<?php

namespace App\Services\AI\Ollama;

use App\Contracts\AI\EmbeddingDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OllamaEmbeddingDriver implements EmbeddingDriverInterface
{
    protected string $baseUrl;
    protected string $defaultModel;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('ai.ollama.base_url', 'http://localhost:11434'), '/');
        $this->defaultModel = config('ai.ollama.embedding_model', 'nomic-embed-text');
        $this->timeout = (int) config('ai.ollama.timeout', 300);
    }

    /**
     * Generate vector embedding for a single text string.
     *
     * @return array<float>
     */
    public function embedText(string $text, ?string $model = null): array
    {
        $batchResult = $this->embedBatch([$text], $model);
        return $batchResult[0] ?? [];
    }

    /**
     * Generate vector embeddings for a batch of text strings.
     *
     * @param  array<string>  $texts
     * @return array<array<float>>
     */
    public function embedBatch(array $texts, ?string $model = null): array
    {
        if (empty($texts)) {
            return [];
        }

        $model = $model ?: $this->defaultModel;

        // Try primary /api/embed endpoint first
        $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/embed", [
            'model' => $model,
            'input' => count($texts) === 1 ? $texts[0] : $texts,
        ]);

        if ($response->successful() && isset($response->json()['embeddings'])) {
            return $response->json()['embeddings'];
        }

        Log::warning('Ollama /api/embed endpoint failed or unsupported. Falling back to /api/embeddings.', [
            'status' => $response->status(),
        ]);

        $embeddings = [];
        foreach ($texts as $text) {
            $legacyResponse = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/embeddings", [
                'model' => $model,
                'prompt' => $text,
            ]);

            if ($legacyResponse->failed()) {
                throw new RuntimeException("Ollama embedding API error: " . $legacyResponse->body());
            }

            $embeddings[] = $legacyResponse->json()['embedding'] ?? [];
        }

        return $embeddings;
    }
}
