<?php

namespace App\Contracts\RAG;

interface ChunkingStrategyInterface
{
    /**
     * Chunk text content into array of text snippets.
     *
     * @return array<array{content: string, token_count: int, metadata: array}>
     */
    public function chunk(string $text, int $chunkSize = 1000, int $overlap = 100): array;
}
