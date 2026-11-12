<?php

namespace Doxa\Mcp\Libraries;

use Illuminate\Support\Facades\DB;

/**
 * Журнал вызовов /mcp: кто, каким токеном, какой метод JSON-RPC и инструмент, с какими аргументами, итог и время.
 */
class McpCallLog
{
    public const TABLE = 'mcp_call_log';

    /**
     * Пишет строку журнала.
     *
     * @param array<string, mixed>|null $arguments аргументы tools/call
     */
    public function write(
        int $tokenId,
        int $userId,
        string $product,
        string $rpcMethod,
        ?string $tool,
        ?array $arguments,
        int $httpStatus,
        bool $isError,
        int $durationMs,
    ): void {
        DB::table(self::TABLE)->insert([
            'token_id' => $tokenId,
            'user_id' => $userId,
            'product' => $product,
            'rpc_method' => mb_substr($rpcMethod, 0, 64),
            'tool' => $tool !== null ? mb_substr($tool, 0, 128) : null,
            'arguments' => $arguments !== null ? json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : null,
            'http_status' => $httpStatus,
            'is_error' => $isError ? 1 : 0,
            'duration_ms' => $durationMs,
            'created_at' => now(),
        ]);
    }
}
