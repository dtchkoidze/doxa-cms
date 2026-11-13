<?php

/**
 * MCP-сервер для ChatGPT и других MCP-клиентов: OAuth 2.1 (PKCE, refresh, регистрация клиента), адрес /mcp, журнал вызовов.
 * Проект включает модуль и задаёт серверы своим config/doxa_mcp.php.
 * Продукт хоста — Registration::authSessionContext(), как у user_auth_sessions; токен действует только на своём продукте.
 * Таблицы — Doxa/Mcp/docs/sql/mcp.sql (вручную, migrate не используется).
 */
return [
    /** Модуль выключен, пока проект не включит его. */
    'enabled' => false,

    /**
     * Код продукта хоста => класс сервера (наследник Laravel\Mcp\Server).
     * Хост без сервера в списке — 404 на /mcp, OAuth и служебных адресах.
     */
    'servers' => [],

    /**
     * Middleware OAuth API и /mcp до проверки токена: маршруты вне группы web (без сессии и CSRF),
     * сюда проект кладёт то, что поднимает продукт хоста.
     */
    'middleware' => [],

    /** Путь MCP-сервера от корня хоста. */
    'path' => 'mcp',

    /** Страница входа; {return} — адрес страницы подтверждения доступа (rawurlencode). */
    'login_url' => '/auth/login?referer={return}',

    /** Разрешённые redirect_uri при регистрации клиента (regex). */
    'redirect_uri_patterns' => [
        '#^https://chatgpt\.com/connector/oauth/[A-Za-z0-9_-]+$#',
        '#^https://chatgpt\.com/connector_platform_oauth_redirect$#',
        // Gemini (Google Connected Apps): oauth-redirect[-sandbox|-test].googleusercontent.com/r/* и /a/*
        '#^https://oauth-redirect(-sandbox|-test)?\.googleusercontent\.com/[ra]/[A-Za-z0-9._-]+$#',
    ],

    /** Единственный scope токена. */
    'scope' => 'mcp',

    'code_ttl_minutes' => 5,

    'access_ttl_minutes' => 60,

    'refresh_ttl_days' => 30,

    /** Запросов к /mcp в минуту на один токен. */
    'rate_per_minute' => 60,
];
