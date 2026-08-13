<?php

namespace App\Services\RAG\Ingestion\Parsers;

use App\Contracts\RAG\DocumentParserInterface;
use InvalidArgumentException;

class TextParser implements DocumentParserInterface
{
    public function supports(string $fileExtension): bool
    {
        return in_array(strtolower($fileExtension), ['txt', 'csv', 'json', 'log']);
    }

    public function parse(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        return file_get_contents($filePath) ?: '';
    }
}
