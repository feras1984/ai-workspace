<?php

namespace Tests\Unit\RAG;

use App\Contracts\AI\EmbeddingDriverInterface;
use App\Contracts\AI\LLMDriverInterface;
use App\Contracts\AI\VectorStoreInterface;
use App\Models\Conversation;
use App\Models\Workspace;
use App\Services\RAG\Pipeline\RAGPipelineEngine;
use App\Services\RAG\Pipeline\RAGRetrievalService;
use App\Services\RAG\Prompt\PromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RAGPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_rag_pipeline_executes_retrieval_and_llm_generation(): void
    {
        $workspace = Workspace::create([
            'name' => 'AI Research',
            'slug' => 'ai-research',
            'system_prompt' => 'You are an AI assistant.',
        ]);

        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'title' => 'RAG Q&A',
            'model' => 'llama3.2',
        ]);

        $mockEmbeddingDriver = $this->createMock(EmbeddingDriverInterface::class);
        $mockEmbeddingDriver->method('embedText')->willReturn([0.1, 0.2, 0.3]);

        $mockVectorStore = $this->createMock(VectorStoreInterface::class);
        $mockVectorStore->method('similaritySearch')->willReturn([
            [
                'chunk_id' => 'chunk-123',
                'document_id' => 'doc-123',
                'content' => 'Laravel 12 supports pgvector for high dimensional AI embeddings.',
                'similarity' => 0.92,
                'metadata' => [],
            ],
        ]);

        $mockLLMDriver = $this->createMock(LLMDriverInterface::class);
        $mockLLMDriver->method('chat')->willReturn([
            'role' => 'assistant',
            'content' => 'Laravel 12 supports pgvector for embeddings.',
            'prompt_tokens' => 45,
            'completion_tokens' => 12,
        ]);

        $retrievalService = new RAGRetrievalService($mockEmbeddingDriver, $mockVectorStore);
        $promptBuilder = new PromptBuilder();
        $engine = new RAGPipelineEngine($retrievalService, $promptBuilder, $mockLLMDriver);

        $result = $engine->run($conversation, 'How does Laravel support pgvector?');

        $this->assertEquals('assistant', $result['role']);
        $this->assertStringContainsString('Laravel 12 supports pgvector', $result['content']);
        $this->assertCount(1, $result['context_sources']);
        $this->assertEquals('chunk-123', $result['context_sources'][0]['chunk_id']);
    }
}
