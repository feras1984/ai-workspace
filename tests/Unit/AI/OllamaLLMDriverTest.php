<?php

namespace Tests\Unit\AI;

use App\Services\AI\Ollama\DTOs\ChatMessageDTO;
use App\Services\AI\Ollama\OllamaLLMDriver;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaLLMDriverTest extends TestCase
{
    public function test_it_executes_non_streaming_chat_completion(): void
    {
        Http::fake([
            '*/api/chat' => Http::response([
                'model' => 'llama3.2',
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Hello! How can I help you today?',
                ],
                'done' => true,
                'prompt_eval_count' => 12,
                'eval_count' => 8,
            ], 200),
        ]);

        $driver = new OllamaLLMDriver();
        $messages = [
            ChatMessageDTO::make('user', 'Hello'),
        ];

        $response = $driver->chat($messages);

        $this->assertEquals('assistant', $response['role']);
        $this->assertEquals('Hello! How can I help you today?', $response['content']);
        $this->assertEquals(12, $response['prompt_tokens']);
        $this->assertEquals(8, $response['completion_tokens']);
    }
}
