<?php

use Doxa\Mcp\Http\Controllers\AuthorizeController;
use Doxa\Mcp\Http\Middleware\EnsureMcpHost;
use Illuminate\Support\Facades\Route;

// Страница подтверждения доступа: обычная веб-страница с сессией и входом сайта.
Route::middleware([EnsureMcpHost::class])->group(function () {
    Route::get('/oauth/authorize', [AuthorizeController::class, 'show']);
    Route::post('/oauth/authorize', [AuthorizeController::class, 'decide']);
});
