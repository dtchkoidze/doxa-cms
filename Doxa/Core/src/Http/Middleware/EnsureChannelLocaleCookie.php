<?php

namespace Doxa\Core\Http\Middleware;

use Closure;
use Doxa\Core\Libraries\ProjectLocale;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ранняя гарантия канала Chlo и валидной cookie локали для группы web.
 * Должен идти после EncryptCookies (cookie шифруется).
 */
class EnsureChannelLocaleCookie
{
    /**
     * Инициализирует канал по host и приводит _project_locale к локали канала.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Канал + cookie + app locale до контроллеров и public_locale
        ProjectLocale::ensureCookieAndLocale();

        return $next($request);
    }
}
