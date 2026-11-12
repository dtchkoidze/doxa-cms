<?php

use Doxa\Mcp\Http\Controllers\McpController;
use Doxa\Mcp\Http\Controllers\MetadataController;
use Doxa\Mcp\Http\Controllers\RegisterController;
use Doxa\Mcp\Http\Controllers\TokenController;
use Doxa\Mcp\Http\Middleware\AuthenticateMcpToken;
use Doxa\Mcp\Http\Middleware\EnsureMcpHost;
use Doxa\Mcp\Http\Middleware\LogMcpCall;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Server\Middleware\ReorderJsonAccept;
use Laravel\Mcp\Server\Middleware\ValidateMcpHeaders;

// Вне группы web: без сессии, cookie и CSRF. Продукт хоста поднимают middleware из doxa_mcp.middleware.
Route::middleware([EnsureMcpHost::class])->group(function () {
    Route::get('/.well-known/oauth-protected-resource/{path?}', [MetadataController::class, 'protectedResource'])
        ->where('path', '.*');
    Route::get('/.well-known/oauth-authorization-server/{path?}', [MetadataController::class, 'authorizationServer'])
        ->where('path', '.*');

    Route::post('/oauth/register', [RegisterController::class, 'register'])->middleware('throttle:20,1');
    Route::post('/oauth/token', [TokenController::class, 'token'])->middleware('throttle:60,1');

    $path = '/' . config('doxa_mcp.path');
    Route::post($path, [McpController::class, 'handle'])->middleware([
        AuthenticateMcpToken::class,
        'throttle:doxa_mcp',
        LogMcpCall::class,
        ReorderJsonAccept::class,
        ValidateMcpHeaders::class,
    ]);
    Route::get($path, [McpController::class, 'methodNotAllowed']);
    Route::delete($path, [McpController::class, 'methodNotAllowed']);
});
