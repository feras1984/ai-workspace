<?php

namespace App\Contracts\AI;

use App\Services\AI\Ollama\DTOs\ChatMessageDTO;
use Generator;

interface LLMDriverInterface
{
    /**
     * Send chat messages to LLM and yield streaming tokens.
     *
     * @param  array<ChatMessageDTO>  $messages
     * @param  array<string, mixed>  $options
     * @return Generator<string>
     */
    public function chatStream(array $messages, ?string $model = null, array $options = []): Generator;

    /**
     * Non-streaming chat completion with optional support for tool calls.
     *
     * @param  array<ChatMessageDTO>  $messages
     * @param  array<array<string, mixed>>  $tools
     * @param  array<string, mixed>  $options
     * @return array{role: string, content: string, tool_calls?: array, prompt_tokens?: int, completion_tokens?: int}
     */
    public function chat(array $messages, ?string $model = null, array $tools = [], array $options = []): array;
}
