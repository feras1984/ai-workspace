<?php

namespace Tests\Unit\RAG;

use App\Services\RAG\Ingestion\Chunkers\RecursiveCharacterChunker;
use Tests\TestCase;

class RecursiveCharacterChunkerTest extends TestCase
{
    public function test_it_chunks_text_by_paragraphs_and_limits_size(): void
    {
        $chunker = new RecursiveCharacterChunker();
        $text = "Paragraph 1 is about Laravel framework.\n\nParagraph 2 is about PostgreSQL pgvector and vector embeddings.\n\nParagraph 3 is about Ollama local LLMs.";

        $chunks = $chunker->chunk($text, chunkSize: 80, overlap: 10);

        $this->assertNotEmpty($chunks);
        $this->assertArrayHasKey('content', $chunks[0]);
        $this->assertArrayHasKey('token_count', $chunks[0]);
        $this->assertArrayHasKey('metadata', $chunks[0]);
    }
}
