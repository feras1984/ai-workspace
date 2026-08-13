<?php

namespace App\Contracts\AI;

interface EmbeddingDriverInterface
{
    /**
     * Generate vector embedding for a text string.
     *
     * @return array<float>
     */
    public function embedText(string $text, ?string $model = null): array;

    /**
     * Generate vector embeddings for a batch of text strings.
     *
     * @param  array<string>  $texts
     * @return array<array<float>>
     */
    public function embedBatch(array $texts, ?string $model = null): array;
}
