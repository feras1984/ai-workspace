<?php

namespace App\Contracts\MCP;

interface MCPClientInterface
{
    public function initialize(): bool;

    /**
     * @return array<array<string, mixed>>
     */
    public function listTools(): array;

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function callTool(string $name, array $arguments = []): array;

    /**
     * @return array<array<string, mixed>>
     */
    public function listResources(): array;

    /**
     * @return array<string, mixed>
     */
    public function readResource(string $uri): array;
}
