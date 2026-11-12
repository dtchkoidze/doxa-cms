<?php

namespace Doxa\Mcp\Libraries;

use Doxa\User\Libraries\Registration as REG;

/**
 * MCP на текущем хосте: код продукта (как у user_auth_sessions), класс сервера продукта, адреса для OAuth-документов.
 */
class McpHost
{
    private bool $contextResolved = false;

    private ?string $context = null;

    /**
     * Возвращает код продукта текущего хоста; null — у хоста нет продукта (MCP недоступен).
     */
    public function context(): ?string
    {
        if (!$this->contextResolved) {
            REG::init();
            $context = REG::authSessionContext();
            $this->context = $context === '' ? null : $context;
            $this->contextResolved = true;
        }

        return $this->context;
    }

    /**
     * Возвращает класс MCP-сервера продукта текущего хоста; null — для продукта сервер не задан.
     *
     * @return class-string<\Laravel\Mcp\Server>|null
     */
    public function serverClass(): ?string
    {
        $context = $this->context();
        if ($context === null) {
            return null;
        }

        $servers = config('doxa_mcp.servers');
        if (!array_key_exists($context, $servers)) {
            return null;
        }

        return $servers[$context];
    }

    /**
     * Возвращает адрес MCP-сервера на текущем хосте (resource в OAuth).
     */
    public function resourceUrl(): string
    {
        return url('/' . config('doxa_mcp.path'));
    }

    /**
     * Возвращает issuer OAuth — корень текущего хоста.
     */
    public function issuer(): string
    {
        return url('/');
    }

    /**
     * Возвращает адрес документа защищённого ресурса (для заголовка WWW-Authenticate).
     */
    public function protectedResourceMetadataUrl(): string
    {
        return url('/.well-known/oauth-protected-resource/' . config('doxa_mcp.path'));
    }
}
