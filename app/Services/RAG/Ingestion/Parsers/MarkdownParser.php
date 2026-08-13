<?php

namespace App\Services\RAG\Ingestion\Parsers;

use App\Contracts\RAG\DocumentParserInterface;
use InvalidArgumentException;

class MarkdownParser implements DocumentParserInterface
{
    public function supports(string $fileExtension): bool
    {
        return in_array(strtolower($fileExtension), ['md', 'markdown']);
    }

    public function parse(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath) ?: '';

        return trim($content);
    }
}
