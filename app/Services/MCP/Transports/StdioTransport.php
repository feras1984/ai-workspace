<?php

namespace App\Services\MCP\Transports;

use App\Contracts\MCP\MCPTransportInterface;
use App\Services\MCP\Protocol\JSONRPCMessage;
use RuntimeException;

class StdioTransport implements MCPTransportInterface
{
    /**
     * @var resource|null
     */
    protected $process = null;

    /**
     * @var array<int, resource>
     */
    protected array $pipes = [];
    protected bool $connected = false;

    /**
     * @param array<string, string> $environmentVars
     */
    public function __construct(
        public string $command,
        public array $environmentVars = []
    ) {}

    public function connect(): bool
    {
        if ($this->connected) {
            return true;
        }

        $command = $this->command;
        if (PHP_OS_FAMILY === 'Windows' && !str_starts_with(strtolower($command), 'cmd')) {
            $command = 'cmd /c ' . $command;
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = array_merge($_ENV, $_SERVER, $this->environmentVars);

        $this->process = proc_open($command, $descriptors, $this->pipes, null, $env);

        if (!is_resource($this->process)) {
            throw new RuntimeException("Failed to launch MCP Stdio server process: {$this->command}");
        }

        stream_set_blocking($this->pipes[1], false);
        $this->connected = true;

        return true;
    }

    public function send(JSONRPCMessage $message): ?JSONRPCMessage
    {
        if (!$this->connected || !isset($this->pipes[0]) || !is_resource($this->pipes[0])) {
            $this->connect();
        }

        $payload = $message->toJson() . "\n";
        fwrite($this->pipes[0], $payload);
        fflush($this->pipes[0]);

        if ($message->id === null) {
            return null;
        }

        $startTime = time();
        $timeout = (int) config('ai.mcp.default_timeout', 60);
        $buffer = '';

        while ((time() - $startTime) < $timeout) {
            $line = fgets($this->pipes[1]);
            if ($line !== false) {
                $buffer .= $line;
                if (str_contains($buffer, "\n")) {
                    return JSONRPCMessage::fromJson(trim($buffer));
                }
            }
            usleep(10000);
        }

        throw new RuntimeException("Timeout waiting for response from MCP server command: {$this->command}");
    }

    public function close(): void
    {
        if ($this->connected) {
            foreach ($this->pipes as $pipe) {
                if (is_resource($pipe)) {
                    @fclose($pipe);
                }
            }

            if (is_resource($this->process)) {
                @proc_terminate($this->process);
                @proc_close($this->process);
            }

            $this->connected = false;
        }
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function __destruct()
    {
        $this->close();
    }
}
