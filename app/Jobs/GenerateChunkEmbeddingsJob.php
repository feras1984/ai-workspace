<?php

namespace App\Jobs;

use App\Contracts\AI\EmbeddingDriverInterface;
use App\Contracts\AI\VectorStoreInterface;
use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateChunkEmbeddingsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    /**
     * @param array<array{content: string, token_count: int, metadata: array}> $chunks
     */
    public function __construct(
        public Document $document,
        public array $chunks
    ) {}

    public function handle(
        EmbeddingDriverInterface $embeddingDriver,
        VectorStoreInterface $vectorStore
    ): void {
        try {
            $contents = array_column($this->chunks, 'content');
            $embeddings = $embeddingDriver->embedBatch($contents);

            foreach ($this->chunks as $index => $chunk) {
                $vector = $embeddings[$index] ?? [];
                $vectorStore->saveChunk(
                    workspaceId: $this->document->workspace_id,
                    documentId: $this->document->id,
                    chunkIndex: $index,
                    content: $chunk['content'],
                    embedding: $vector,
                    tokenCount: $chunk['token_count'] ?? 0,
                    metadata: $chunk['metadata'] ?? []
                );
            }

            $this->document->update([
                'status' => 'completed',
                'metadata' => array_merge($this->document->metadata ?? [], [
                    'chunks_count' => count($this->chunks),
                ]),
            ]);
        } catch (Throwable $e) {
            $this->document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
