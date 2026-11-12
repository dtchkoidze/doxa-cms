<?php

namespace Doxa\Mcp\Http\Controllers;

use Doxa\Mcp\Libraries\McpHost;
use Illuminate\Http\Response;
use Laravel\Mcp\Server\Transport\HttpTransport;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Адрес /mcp: сообщение JSON-RPC передаётся серверу продукта хоста (doxa_mcp.servers).
 */
class McpController
{
    public function __construct(
        private readonly McpHost $host,
    ) {}

    /**
     * Обрабатывает сообщение MCP-клиента сервером продукта и отдаёт ответ JSON-RPC.
     */
    public function handle(): Response|StreamedResponse
    {
        $serverClass = $this->host->serverClass();
        $transport = new HttpTransport(request());
        $server = new $serverClass($transport);
        $server->start();

        return $transport->run();
    }

    /**
     * Отвечает 405 на GET и DELETE: поток событий от сервера и закрытие сессии не поддерживаются.
     */
    public function methodNotAllowed(): Response
    {
        return response('', 405)->header('Allow', 'POST');
    }
}
