<?php

namespace App\Services\AI\Ollama\DTOs;

class ChatMessageDTO
{
    /**
     * @param array<array<string, mixed>>|null $toolCalls
     * @param array<array<string, mixed>>|null $contextSources
     */
    public function __construct(
        public string $role,
        public string $content,
        public ?array $toolCalls = null,
        public ?array $contextSources = null
    ) {}

    public static function make(string $role, string $content, ?array $toolCalls = null, ?array $contextSources = null): self
    {
        return new self($role, $content, $toolCalls, $contextSources);
    }

    /**
     * @return array{role: string, content: string, tool_calls?: array}
     */
    public function toArray(): array
    {
        $payload = [
            'role' => $this->role,
            'content' => $this->content,
        ];

        if ($this->toolCalls !== null) {
            $payload['tool_calls'] = $this->toolCalls;
        }

        return $payload;
    }
}
