<?php

namespace App\Services\AI\Ollama;

use App\Contracts\AI\LLMDriverInterface;
use App\Services\AI\Ollama\DTOs\ChatMessageDTO;
use Generator;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaLLMDriver implements LLMDriverInterface
{
    protected string $baseUrl;
    protected string $defaultModel;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('ai.ollama.base_url', 'http://localhost:11434'), '/');
        $this->defaultModel = config('ai.ollama.default_model', 'llama3.2');
        $this->timeout = (int) config('ai.ollama.timeout', 300);
    }

    /**
     * Send chat messages to LLM and yield streaming content tokens.
     *
     * @param  array<ChatMessageDTO>  $messages
     * @param  array<string, mixed>  $options
     * @return Generator<string>
     */
    public function chatStream(array $messages, ?string $model = null, array $options = []): Generator
    {
        $model = $model ?: $this->defaultModel;
        $formattedMessages = array_map(fn (ChatMessageDTO $msg) => $msg->toArray(), $messages);

        $payload = [
            'model' => $model,
            'messages' => $formattedMessages,
            'stream' => true,
        ];

        if (!empty($options)) {
            $payload['options'] = $options;
        }

        $streamUrl = "{$this->baseUrl}/api/chat";

        $ch = curl_init($streamUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $buffer = '';

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$buffer) {
            $buffer .= $chunk;
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line !== '') {
                    $json = json_decode($line, true);
                    if (isset($json['message']['content'])) {
                        echo $json['message']['content'];
                    }
                }
            }
            return strlen($chunk);
        });

        // For generator returning in PHP context:
        // We implement streaming via HTTP stream using Guzzle/Http stream client
        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout($this->timeout)
            ->withOptions(['stream' => true])
            ->post("{$this->baseUrl}/api/chat", $payload);

        $body = $response->toPsrResponse()->getBody();
        $streamBuffer = '';

        while (!$body->eof()) {
            $streamBuffer .= $body->read(1024);
            while (($pos = strpos($streamBuffer, "\n")) !== false) {
                $line = trim(substr($streamBuffer, 0, $pos));
                $streamBuffer = substr($streamBuffer, $pos + 1);

                if ($line === '') {
                    continue;
                }

                $data = json_decode($line, true);
                if (is_array($data) && isset($data['message']['content'])) {
                    yield $data['message']['content'];
                }
            }
        }
    }

    /**
     * Non-streaming chat completion with optional support for tool calls.
     *
     * @param  array<ChatMessageDTO>  $messages
     * @param  array<array<string, mixed>>  $tools
     * @param  array<string, mixed>  $options
     * @return array{role: string, content: string, tool_calls?: array, prompt_tokens?: int, completion_tokens?: int}
     */
    public function chat(array $messages, ?string $model = null, array $tools = [], array $options = []): array
    {
        $model = $model ?: $this->defaultModel;
        $formattedMessages = array_map(fn (ChatMessageDTO $msg) => $msg->toArray(), $messages);

        $payload = [
            'model' => $model,
            'messages' => $formattedMessages,
            'stream' => false,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        if (!empty($options)) {
            $payload['options'] = $options;
        }

        $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/chat", $payload);

        if ($response->failed()) {
            throw new RuntimeException("Ollama LLM API error: " . $response->body());
        }

        $data = $response->json();
        $message = $data['message'] ?? [];

        return [
            'role' => $message['role'] ?? 'assistant',
            'content' => $message['content'] ?? '',
            'tool_calls' => $message['tool_calls'] ?? null,
            'prompt_tokens' => $data['prompt_eval_count'] ?? null,
            'completion_tokens' => $data['eval_count'] ?? null,
        ];
    }
}
