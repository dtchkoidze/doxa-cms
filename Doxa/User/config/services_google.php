<?php

/**
 * Copy into the host application's config/services.php under the "google" key,
 * and set env vars in the host .env:
 *
 * GOOGLE_CLIENT_ID=
 * GOOGLE_CLIENT_SECRET=
 * GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
 *
 * Google Cloud Console → OAuth 2.0 Client → Authorized redirect URIs
 * must match GOOGLE_REDIRECT_URI exactly (http://your-local-domain/auth/google/callback).
 *
 * Also run the migration that adds users.google_id (nullable unique).
 * Add google_id to App\Models\User $fillable on the host.
 */

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],
];
