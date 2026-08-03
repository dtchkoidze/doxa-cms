<?php

/**
 * Copy into the host application's config/services.php under the "facebook" key,
 * and set env vars in the host .env:
 *
 * FACEBOOK_CLIENT_ID=
 * FACEBOOK_CLIENT_SECRET=
 * FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"
 *
 * Local Development: use http://localhost — Meta allows localhost redirects
 * automatically in Development mode (do not add them to Valid OAuth Redirect URIs).
 *
 * Production: Meta → Facebook Login → Settings → Valid OAuth Redirect URIs
 * must include FACEBOOK_REDIRECT_URI exactly.
 *
 * Also run SQL from Doxa/User/docs/update.sql (facebook_id + nullable email).
 */

return [
    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],
];
