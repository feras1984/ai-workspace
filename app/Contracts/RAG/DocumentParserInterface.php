<?php

namespace App\Contracts\RAG;

interface DocumentParserInterface
{
    /**
     * Check if the parser supports the given file extension/type.
     */
    public function supports(string $fileExtension): bool;

    /**
     * Parse document file content and extract plain text.
     */
    public function parse(string $filePath): string;
}
