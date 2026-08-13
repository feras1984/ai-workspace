<?php

namespace App\Contracts\AI;

interface VectorStoreInterface
{
    /**
     * Store document chunk embedding into vector store.
     *
     * @param  array<float>  $embedding
     * @param  array<string, mixed>  $metadata
     */
    public function saveChunk(
        string $workspaceId,
        string $documentId,
        int $chunkIndex,
        string $content,
        array $embedding,
        int $tokenCount = 0,
        array $metadata = []
    ): string;

    /**
     * Perform Cosine distance similarity search.
     *
     * @param  array<float>  $queryVector
     * @return array<array{chunk_id: string, document_id: string, content: string, similarity: float, metadata: array}>
     */
    public function similaritySearch(
        string $workspaceId,
        array $queryVector,
        int $limit = 5,
        float $minSimilarity = 0.5
    ): array;

    /**
     * Delete vectors by document ID.
     */
    public function deleteByDocument(string $documentId): bool;
}
