<?php

namespace App\Services\RAG\Ingestion\Parsers;

use App\Contracts\RAG\DocumentParserInterface;
use InvalidArgumentException;

class PdfParser implements DocumentParserInterface
{
    public function supports(string $fileExtension): bool
    {
        return strtolower($fileExtension) === 'pdf';
    }

    public function parse(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }

        // Extract text blocks from PDF objects
        preg_match_all('/BT[\s\S]*?ET/m', $content, $matches);
        $extractedText = '';

        if (!empty($matches[0])) {
            foreach ($matches[0] as $textBlock) {
                preg_match_all('/\((.*?)\)\s*Tj/m', $textBlock, $strings);
                if (!empty($strings[1])) {
                    $extractedText .= implode(' ', $strings[1]) . "\n";
                }
            }
        }

        if (trim($extractedText) !== '') {
            return trim($extractedText);
        }

        // Clean printable ASCII fallback
        $text = preg_replace('/[^\x20-\x7E\x0A\x0D]/', ' ', $content);
        $lines = array_filter(explode("\n", (string) $text), fn ($line) => strlen(trim($line)) > 20);

        return implode("\n", $lines);
    }
}
