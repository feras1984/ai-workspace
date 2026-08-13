<?php

namespace App\Services\RAG\Pipeline;

use App\Contracts\AI\EmbeddingDriverInterface;
use App\Contracts\AI\VectorStoreInterface;

class RAGRetrievalService
{
    public function __construct(
        protected EmbeddingDriverInterface $embeddingDriver,
        protected VectorStoreInterface $vectorStore
    ) {}

    /**
     * Retrieve relevant document chunks for a user query.
     *
     * @return array<array{chunk_id: string, document_id: string, content: string, similarity: float, metadata: array}>
     */
    public function retrieveContext(
        string $workspaceId,
        string $query,
        ?int $limit = null,
        ?float $minSimilarity = null
    ): array {
        $limit = $limit ?: (int) config('ai.rag.default_top_k', 5);
        $minSimilarity = $minSimilarity ?: (float) config('ai.rag.min_similarity', 0.5);

        $queryVector = $this->embeddingDriver->embedText($query);

        if (empty($queryVector)) {
            return [];
        }

        return $this->vectorStore->similaritySearch(
            workspaceId: $workspaceId,
            queryVector: $queryVector,
            limit: $limit,
            minSimilarity: $minSimilarity
        );
    }
}
