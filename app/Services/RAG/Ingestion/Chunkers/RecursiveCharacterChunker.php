<?php

namespace App\Services\RAG\Ingestion\Chunkers;

use App\Contracts\RAG\ChunkingStrategyInterface;

class RecursiveCharacterChunker implements ChunkingStrategyInterface
{
    /**
     * @var array<string>
     */
    protected array $separators = ["\n\n", "\n", ". ", "? ", "! ", " "];

    /**
     * Chunk text into array of structured snippets with estimated token counts.
     *
     * @return array<array{content: string, token_count: int, metadata: array}>
     */
    public function chunk(string $text, int $chunkSize = 1000, int $overlap = 100): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $rawChunks = $this->splitText($text, $chunkSize, $overlap, $this->separators);
        $result = [];

        foreach ($rawChunks as $index => $content) {
            $trimmed = trim($content);
            if ($trimmed === '') {
                continue;
            }

            // Estimate tokens (~4 characters per token)
            $tokenCount = (int) ceil(mb_strlen($trimmed) / 4);

            $result[] = [
                'content' => $trimmed,
                'token_count' => $tokenCount,
                'metadata' => [
                    'chunk_index' => $index,
                    'char_length' => mb_strlen($trimmed),
                ],
            ];
        }

        return $result;
    }

    /**
     * Recursively split text using hierarchy of natural separators.
     *
     * @param array<string> $separators
     * @return array<string>
     */
    protected function splitText(string $text, int $chunkSize, int $overlap, array $separators): array
    {
        $finalChunks = [];

        if (mb_strlen($text) <= $chunkSize) {
            return [$text];
        }

        $separator = array_shift($separators);
        $splits = $separator !== null ? explode($separator, $text) : str_split($text, $chunkSize);

        $currentChunk = '';

        foreach ($splits as $split) {
            $piece = ($currentChunk === '' || $separator === null) ? $split : $currentChunk . $separator . $split;

            if (mb_strlen($piece) <= $chunkSize) {
                $currentChunk = $piece;
            } else {
                if ($currentChunk !== '') {
                    $finalChunks[] = $currentChunk;
                }

                if (mb_strlen($split) > $chunkSize && !empty($separators)) {
                    $subChunks = $this->splitText($split, $chunkSize, $overlap, $separators);
                    $finalChunks = array_merge($finalChunks, $subChunks);
                    $currentChunk = '';
                } else {
                    $currentChunk = $split;
                }
            }
        }

        if ($currentChunk !== '') {
            $finalChunks[] = $currentChunk;
        }

        // Apply overlap between adjacent chunks
        if ($overlap > 0 && count($finalChunks) > 1) {
            $overlappedChunks = [];
            for ($i = 0; $i < count($finalChunks); $i++) {
                if ($i === 0) {
                    $overlappedChunks[] = $finalChunks[$i];
                } else {
                    $prevChunk = $finalChunks[$i - 1];
                    $overlapPrefix = mb_substr($prevChunk, max(0, mb_strlen($prevChunk) - $overlap));
                    $overlappedChunks[] = $overlapPrefix . " " . $finalChunks[$i];
                }
            }
            return $overlappedChunks;
        }

        return $finalChunks;
    }
}
