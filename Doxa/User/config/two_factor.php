<?php

/**
 * 2FA. issuer накладывает хост из текущего product (домен/бренд), пакет сам product не резолвит.
 */
return [
    'issuer' => env('APP_NAME', ''),
    'code_ttl_minutes' => 5,
    'resend_seconds' => 60,
    'max_attempts' => 5,
    'decay_minutes' => 15,
];
