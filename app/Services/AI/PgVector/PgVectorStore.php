<?php

namespace App\Services\AI\PgVector;

use App\Contracts\AI\VectorStoreInterface;
use App\Models\DocumentChunk;
use Illuminate\Support\Facades\DB;

class PgVectorStore implements VectorStoreInterface
{
    /**
     * Store chunk embedding into database.
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
    ): string {
        $chunk = DocumentChunk::create([
            'workspace_id' => $workspaceId,
            'document_id' => $documentId,
            'chunk_index' => $chunkIndex,
            'content' => $content,
            'token_count' => $tokenCount,
            'embedding' => $embedding,
            'metadata' => $metadata,
        ]);

        return $chunk->id;
    }

    /**
     * Similarity search using Cosine distance operator (<=>).
     *
     * @param  array<float>  $queryVector
     * @return array<array{chunk_id: string, document_id: string, content: string, similarity: float, metadata: array}>
     */
    public function similaritySearch(
        string $workspaceId,
        array $queryVector,
        int $limit = 5,
        float $minSimilarity = 0.5
    ): array {
        if (empty($queryVector)) {
            return [];
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'pgsql') {
            $vectorString = '[' . implode(',', $queryVector) . ']';

            $results = DB::select("
                SELECT id, document_id, content, metadata,
                       (1 - (embedding <=> ?::vector)) AS similarity
                FROM document_chunks
                WHERE workspace_id = ?
                  AND (1 - (embedding <=> ?::vector)) >= ?
                ORDER BY embedding <=> ?::vector ASC
                LIMIT ?
            ", [$vectorString, $workspaceId, $vectorString, $minSimilarity, $vectorString, $limit]);

            return array_map(function ($row) {
                return [
                    'chunk_id' => $row->id,
                    'document_id' => $row->document_id,
                    'content' => $row->content,
                    'similarity' => (float) $row->similarity,
                    'metadata' => is_string($row->metadata) ? json_decode($row->metadata, true) : (array) $row->metadata,
                ];
            }, $results);
        }

        // Fallback for non-PostgreSQL drivers (e.g. SQLite testing)
        $chunks = DocumentChunk::where('workspace_id', $workspaceId)->get();
        $matched = [];

        foreach ($chunks as $chunk) {
            $embedding = $chunk->embedding;
            if (empty($embedding)) {
                continue;
            }

            $similarity = $this->calculateCosineSimilarity($queryVector, $embedding);
            if ($similarity >= $minSimilarity) {
                $matched[] = [
                    'chunk_id' => $chunk->id,
                    'document_id' => $chunk->document_id,
                    'content' => $chunk->content,
                    'similarity' => $similarity,
                    'metadata' => $chunk->metadata ?? [],
                ];
            }
        }

        usort($matched, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($matched, 0, $limit);
    }

    /**
     * Delete vectors by document ID.
     */
    public function deleteByDocument(string $documentId): bool
    {
        return DocumentChunk::where('document_id', $documentId)->delete() >= 0;
    }

    /**
     * Pure PHP Cosine Similarity fallback calculation.
     *
     * @param  array<float>  $vecA
     * @param  array<float>  $vecB
     */
    protected function calculateCosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $count = min(count($vecA), count($vecB));
        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] * $vecA[$i];
            $normB += $vecB[$i] * $vecB[$i];
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
