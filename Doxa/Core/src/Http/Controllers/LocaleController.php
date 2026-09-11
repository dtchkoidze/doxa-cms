<?php

namespace Doxa\Core\Http\Controllers;

use Doxa\Core\Libraries\ProjectLocale;
use Illuminate\Http\RedirectResponse;

/**
 * Смена локали через cookie (для /auth и прочих страниц без префикса в URL).
 */
class LocaleController
{
    /**
     * Пишет валидную для канала локаль в cookie и возвращает на redirect.
     * Возвращает редирект на безопасный относительный URL.
     */
    public function set(string $code): RedirectResponse
    {
        $channel = ProjectLocale::ensureChannel();
        if (!$channel || !ProjectLocale::channelHasCode($channel, $code)) {
            abort(404);
        }

        ProjectLocale::applyLocale($code);

        return redirect()->to($this->safeRedirectTarget());
    }

    /**
     * Возвращает относительный путь для редиректа (без open-redirect).
     */
    private function safeRedirectTarget(): string
    {
        // return — не redirect: глобальный RedirectFromPwa перехватывает ?redirect=
        $target = request('return');
        if (!is_string($target) || $target === '') {
            $target = request('redirect');
        }
        if (!is_string($target) || $target === '') {
            $referer = request()->headers->get('referer');
            if (is_string($referer) && $referer !== '') {
                $path = parse_url($referer, PHP_URL_PATH);
                $query = parse_url($referer, PHP_URL_QUERY);
                $target = is_string($path) ? $path : '/';
                if (is_string($query) && $query !== '') {
                    $target .= '?' . $query;
                }
            } else {
                $target = '/';
            }
        }

        if (!str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return '/';
        }

        return $target;
    }
}
