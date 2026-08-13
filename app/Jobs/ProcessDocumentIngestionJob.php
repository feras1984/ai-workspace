<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\RAG\Ingestion\DocumentIngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessDocumentIngestionJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public Document $document) {}

    public function handle(DocumentIngestionService $ingestionService): void
    {
        try {
            $this->document->update(['status' => 'processing']);

            $rawText = $ingestionService->parseDocument($this->document);
            $chunks = $ingestionService->chunkDocument($this->document, $rawText);

            if (empty($chunks)) {
                $this->document->update([
                    'status' => 'completed',
                    'metadata' => array_merge($this->document->metadata ?? [], ['chunks_count' => 0]),
                ]);
                return;
            }

            dispatch(new GenerateChunkEmbeddingsJob($this->document, $chunks));
        } catch (Throwable $e) {
            $this->document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
