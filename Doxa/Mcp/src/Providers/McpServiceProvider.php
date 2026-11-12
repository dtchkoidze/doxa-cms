<?php

namespace Doxa\Mcp\Providers;

use Doxa\Mcp\Http\Middleware\AuthenticateMcpToken;
use Doxa\Mcp\Libraries\McpHost;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Модуль MCP: OAuth для MCP-клиентов (ChatGPT) и адрес /mcp. Работает, только если проект включил doxa_mcp.enabled.
 */
class McpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/doxa_mcp.php', 'doxa_mcp');

        $this->app->singleton(McpHost::class);
    }

    public function boot(): void
    {
        if (!config('doxa_mcp.enabled')) {
            return;
        }

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'doxa-mcp');

        RateLimiter::for('doxa_mcp', fn () => Limit::perMinute(config('doxa_mcp.rate_per_minute'))
            ->by('doxa_mcp:' . request()->attributes->get(AuthenticateMcpToken::TOKEN_ATTRIBUTE)->id));

        Route::middleware(config('doxa_mcp.middleware'))->group(__DIR__ . '/../Routes/api.php');
        Route::middleware('web')->group(__DIR__ . '/../Routes/web.php');
    }
}
