<?php

/**
 * Пути API и клиентский driver для auth-сессий.
 */
return [
    'mobile_login_path' => 'api/auth/mobile/login',
    'mobile_logout_path' => 'api/auth/mobile/logout',
    'sessions_list_path' => 'api/auth/sessions',
    'sessions_revoke_path' => 'api/auth/sessions/{id}',

    /**
     * session — обычный cookie login (auth/api/login).
     * mobile_token — POST mobile login, token в localStorage (sim / native).
     */
    'auth_driver' => env('USER_AUTH_DRIVER', 'session'),

    /** Ключ localStorage для plain mobile token. */
    'token_storage_key' => env('USER_AUTH_TOKEN_STORAGE_KEY', 'mobile_api_token'),

    /** Redirect после успешного mobile_token login. */
    'auth_success_url' => env('USER_AUTH_SUCCESS_URL', '/welcome'),
];
