<?php

namespace App\Services\RAG\Ingestion;

use App\Contracts\RAG\ChunkingStrategyInterface;
use App\Contracts\RAG\DocumentParserInterface;
use App\Models\Document;
use App\Services\RAG\Ingestion\Chunkers\RecursiveCharacterChunker;
use App\Services\RAG\Ingestion\Parsers\MarkdownParser;
use App\Services\RAG\Ingestion\Parsers\PdfParser;
use App\Services\RAG\Ingestion\Parsers\TextParser;
use InvalidArgumentException;

class DocumentIngestionService
{
    /**
     * @var array<DocumentParserInterface>
     */
    protected array $parsers;
    protected ChunkingStrategyInterface $chunker;

    public function __construct(?ChunkingStrategyInterface $chunker = null)
    {
        $this->parsers = [
            new TextParser(),
            new MarkdownParser(),
            new PdfParser(),
        ];
        $this->chunker = $chunker ?: new RecursiveCharacterChunker();
    }

    /**
     * Parse raw document text using supported parser.
     */
    public function parseDocument(Document $document): string
    {
        $extension = pathinfo($document->file_path, PATHINFO_EXTENSION) ?: $document->file_type;

        foreach ($this->parsers as $parser) {
            if ($parser->supports($extension)) {
                return $parser->parse($document->file_path);
            }
        }

        return (new TextParser())->parse($document->file_path);
    }

    /**
     * Chunk document text into snippets.
     *
     * @return array<array{content: string, token_count: int, metadata: array}>
     */
    public function chunkDocument(Document $document, string $rawText): array
    {
        $chunkSize = (int) config('ai.rag.chunk_size', 1000);
        $chunkOverlap = (int) config('ai.rag.chunk_overlap', 100);

        return $this->chunker->chunk($rawText, $chunkSize, $chunkOverlap);
    }
}
