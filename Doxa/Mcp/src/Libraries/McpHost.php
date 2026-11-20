<?php

namespace Doxa\Mcp\Libraries;

use Doxa\User\Libraries\Registration as REG;
use RuntimeException;

/**
 * MCP на текущем хосте: код продукта хоста (как у user_auth_sessions), серверы по пути /mcp/{ключ},
 * адреса resource для OAuth по этому пути.
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
     * Возвращает карту «ключ пути MCP → класс сервера» для продукта текущего хоста; пусто — MCP на хосте нет.
     *
     * @return array<string, class-string<\Laravel\Mcp\Server>>
     */
    public function servers(): array
    {
        $context = $this->context();
        if ($context === null) {
            return [];
        }

        $servers = config('doxa_mcp.servers');
        if (!is_array($servers) || !array_key_exists($context, $servers)) {
            return [];
        }

        $forHost = $servers[$context];
        if (!is_array($forHost)) {
            throw new RuntimeException(
                'doxa_mcp.servers.' . $context . ' must be an array of mcp path key => server class'
            );
        }

        return $forHost;
    }

    /**
     * Возвращает true, если на хосте задан хотя бы один MCP-сервер по пути.
     */
    public function hasServers(): bool
    {
        return $this->servers() !== [];
    }

    /**
     * Возвращает ключ MCP из маршрута /mcp/{mcpProduct}, из path well-known или из query resource; иначе null.
     */
    public function mcpKey(): ?string
    {
        $routeKey = request()->route('mcpProduct');
        if (is_string($routeKey) && $routeKey !== '') {
            return $routeKey;
        }

        $path = request()->route('path');
        if (is_string($path) && $path !== '') {
            $prefix = config('doxa_mcp.path');
            if (str_starts_with($path, $prefix . '/')) {
                $key = substr($path, strlen($prefix) + 1);
                if ($key !== '' && !str_contains($key, '/')) {
                    return $key;
                }
            }
        }

        $resource = request()->input('resource');
        if (is_string($resource) && $resource !== '') {
            return $this->mcpKeyFromResourceUrl($resource);
        }

        return null;
    }

    /**
     * Возвращает класс MCP-сервера для текущего ключа пути; null — ключ не задан или на хосте такого сервера нет.
     *
     * @return class-string<\Laravel\Mcp\Server>|null
     */
    public function serverClass(): ?string
    {
        $key = $this->mcpKey();
        if ($key === null) {
            return null;
        }

        $servers = $this->servers();
        if (!array_key_exists($key, $servers)) {
            return null;
        }

        return $servers[$key];
    }

    /**
     * Возвращает адрес MCP-сервера (resource в OAuth) для ключа пути; без ключа — из текущего запроса.
     *
     * @throws RuntimeException нет ключа пути MCP
     */
    public function resourceUrl(?string $mcpKey = null): string
    {
        $key = $mcpKey ?? $this->mcpKey();
        if ($key === null || $key === '') {
            throw new RuntimeException('MCP path key is required for resource URL');
        }

        return url('/' . config('doxa_mcp.path') . '/' . $key);
    }

    /**
     * Возвращает true, если $resource — один из адресов /mcp/{ключ} текущего хоста.
     */
    public function isValidResource(string $resource): bool
    {
        $normalized = rtrim($resource, '/');
        foreach (array_keys($this->servers()) as $key) {
            if ($normalized === rtrim($this->resourceUrl($key), '/')) {
                return true;
            }
        }

        return false;
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
     *
     * @throws RuntimeException нет ключа пути MCP
     */
    public function protectedResourceMetadataUrl(?string $mcpKey = null): string
    {
        $key = $mcpKey ?? $this->mcpKey();
        if ($key === null || $key === '') {
            throw new RuntimeException('MCP path key is required for protected resource metadata URL');
        }

        return url('/.well-known/oauth-protected-resource/' . config('doxa_mcp.path') . '/' . $key);
    }

    /**
     * Достаёт ключ MCP из полного URL resource (/mcp/{ключ}).
     */
    private function mcpKeyFromResourceUrl(string $resource): ?string
    {
        $path = parse_url($resource, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return null;
        }

        $prefix = '/' . config('doxa_mcp.path') . '/';
        if (!str_starts_with($path, $prefix)) {
            return null;
        }

        $key = substr($path, strlen($prefix));
        if ($key === '' || str_contains($key, '/')) {
            return null;
        }

        return $key;
    }
}
