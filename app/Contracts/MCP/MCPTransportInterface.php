<?php

namespace App\Contracts\MCP;

use App\Services\MCP\Protocol\JSONRPCMessage;

interface MCPTransportInterface
{
    public function connect(): bool;
    public function send(JSONRPCMessage $message): ?JSONRPCMessage;
    public function close(): void;
    public function isConnected(): bool;
}
