<?php

namespace Tests\Unit\AI;

use App\Services\AI\Ollama\OllamaEmbeddingDriver;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaEmbeddingDriverTest extends TestCase
{
    public function test_it_generates_single_text_embedding(): void
    {
        Http::fake([
            '*/api/embed' => Http::response([
                'model' => 'nomic-embed-text',
                'embeddings' => [
                    [0.1, 0.2, 0.3],
                ],
            ], 200),
        ]);

        $driver = new OllamaEmbeddingDriver();
        $embedding = $driver->embedText('Hello world');

        $this->assertCount(3, $embedding);
        $this->assertEquals([0.1, 0.2, 0.3], $embedding);
    }

    public function test_it_generates_batch_text_embeddings(): void
    {
        Http::fake([
            '*/api/embed' => Http::response([
                'model' => 'nomic-embed-text',
                'embeddings' => [
                    [0.1, 0.2, 0.3],
                    [0.4, 0.5, 0.6],
                ],
            ], 200),
        ]);

        $driver = new OllamaEmbeddingDriver();
        $embeddings = $driver->embedBatch(['Hello world', 'Second text']);

        $this->assertCount(2, $embeddings);
        $this->assertEquals([0.1, 0.2, 0.3], $embeddings[0]);
        $this->assertEquals([0.4, 0.5, 0.6], $embeddings[1]);
    }
}
