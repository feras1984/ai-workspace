<?php

namespace App\Services\MCP\Protocol;

class JSONRPCMessage
{
    /**
     * @param array<string, mixed>|null $params
     */
    public function __construct(
        public string $jsonrpc = '2.0',
        public int|string|null $id = null,
        public ?string $method = null,
        public ?array $params = null,
        public mixed $result = null,
        public mixed $error = null
    ) {}

    /**
     * @param array<string, mixed>|null $params
     */
    public static function request(int|string $id, string $method, ?array $params = null): self
    {
        return new self(jsonrpc: '2.0', id: $id, method: $method, params: $params);
    }

    /**
     * @param array<string, mixed>|null $params
     */
    public static function notification(string $method, ?array $params = null): self
    {
        return new self(jsonrpc: '2.0', id: null, method: $method, params: $params);
    }

    public static function response(int|string $id, mixed $result): self
    {
        return new self(jsonrpc: '2.0', id: $id, result: $result);
    }

    public static function fromJson(string $jsonString): ?self
    {
        $data = json_decode($jsonString, true);
        if (!is_array($data)) {
            return null;
        }

        return new self(
            jsonrpc: $data['jsonrpc'] ?? '2.0',
            id: $data['id'] ?? null,
            method: $data['method'] ?? null,
            params: $data['params'] ?? null,
            result: $data['result'] ?? null,
            error: $data['error'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = ['jsonrpc' => $this->jsonrpc];

        if ($this->id !== null) {
            $payload['id'] = $this->id;
        }

        if ($this->method !== null) {
            $payload['method'] = $this->method;
        }

        if ($this->params !== null) {
            $payload['params'] = $this->params;
        }

        if ($this->result !== null) {
            $payload['result'] = $this->result;
        }

        if ($this->error !== null) {
            $payload['error'] = $this->error;
        }

        return $payload;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
