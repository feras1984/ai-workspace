<?php

namespace App\Services\AI\Ollama\DTOs;

class ChatResponseStreamDTO
{
    /**
     * @param array<array<string, mixed>>|null $toolCalls
     */
    public function __construct(
        public string $content,
        public bool $done = false,
        public ?array $toolCalls = null,
        public ?int $promptTokens = null,
        public ?int $completionTokens = null
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromOllamaChunk(array $data): self
    {
        $message = $data['message'] ?? [];
        $content = $message['content'] ?? '';
        $done = (bool) ($data['done'] ?? false);
        $toolCalls = $message['tool_calls'] ?? null;
        $promptTokens = $data['prompt_eval_count'] ?? null;
        $completionTokens = $data['eval_count'] ?? null;

        return new self($content, $done, $toolCalls, $promptTokens, $completionTokens);
    }
}
