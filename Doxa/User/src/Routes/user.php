<?php

use Illuminate\Support\Facades\Route;
use Doxa\User\Http\Controllers\AddEmailController;
use Doxa\User\Http\Controllers\Registration\RegistrationController;
use Doxa\User\Http\Controllers\Registration\ApiController as RegistrationApiController;
use Doxa\User\Http\Controllers\SocialAuth\GoogleController;
use Doxa\User\Http\Controllers\SocialAuth\FacebookController;

// Social OAuth — outside authorization middleware (no pending auth_data / clear on entry)
Route::group(['middleware' => ['web'], 'prefix' => config('app.auth_prefix')], function () {
    if (config('services.google.auth_enabled')) {
        Route::get('/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
        Route::get('/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
        Route::get('/google/link', [GoogleController::class, 'linkPage'])->name('auth.google.link');
        Route::post('/google/link/password', [GoogleController::class, 'linkWithPassword'])->name('auth.google.link.password');
        Route::post('/google/link/magic', [GoogleController::class, 'sendMagicLink'])->name('auth.google.link.send_magic');
        Route::get('/google/link/magic/{token}', [GoogleController::class, 'magicLink'])->name('auth.google.link.magic');
        Route::get('/google/link/cancel', [GoogleController::class, 'cancelLink'])->name('auth.google.link.cancel');
    }

    if (config('services.facebook.auth_enabled')) {
        Route::get('/facebook/redirect', [FacebookController::class, 'redirect'])->name('auth.facebook.redirect');
        Route::get('/facebook/callback', [FacebookController::class, 'callback'])->name('auth.facebook.callback');
        Route::get('/facebook/link', [FacebookController::class, 'linkPage'])->name('auth.facebook.link');
        Route::post('/facebook/link/password', [FacebookController::class, 'linkWithPassword'])->name('auth.facebook.link.password');
        Route::post('/facebook/link/magic', [FacebookController::class, 'sendMagicLink'])->name('auth.facebook.link.send_magic');
        Route::get('/facebook/link/magic/{token}', [FacebookController::class, 'magicLink'])->name('auth.facebook.link.magic');
        Route::get('/facebook/link/cancel', [FacebookController::class, 'cancelLink'])->name('auth.facebook.link.cancel');
    }

    Route::get('/two-factor', [\Doxa\User\Http\Controllers\TwoFactor\TwoFactorChallengeController::class, 'page'])
        ->name('auth.two_factor');
    Route::get('/api/two-factor/challenge', [\Doxa\User\Http\Controllers\TwoFactor\TwoFactorChallengeController::class, 'state'])
        ->name('auth.api.two_factor.challenge');
    Route::post('/api/two-factor/challenge/channel', [\Doxa\User\Http\Controllers\TwoFactor\TwoFactorChallengeController::class, 'switchChannel'])
        ->name('auth.api.two_factor.challenge_channel');
    Route::post('/api/two-factor/challenge', [\Doxa\User\Http\Controllers\TwoFactor\TwoFactorChallengeController::class, 'verify'])
        ->name('auth.api.two_factor.challenge_verify');
});

Route::group(['middleware' => ['web', 'authorization'], 'prefix' => config('app.auth_prefix')], function () {

    // login page
    Route::get('/login', [RegistrationController::class, 'login'])->name('auth.login');

    // registration page
    Route::get('/register', [RegistrationController::class, 'register'])->name('auth.register');

    // rcovery page
    Route::get('/recovery', [RegistrationController::class, 'recovery'])->name('auth.recovery');

    // verification page
    Route::get('/{method}/verify/', [RegistrationController::class, 'verify'])->name('auth.verify');

    // password setup page
    Route::get('/{method}/password', [RegistrationController::class, 'password'])->name('auth.password');

    // verification by link
    Route::get('/{method}/verification-link', [RegistrationController::class, 'verificationLink'])->name('auth.verification_link');

    // account suspended page
    Route::get('/suspended', [RegistrationController::class, 'suspended'])->name('auth.suspended');

    // waiting for activate
    Route::get('/waiting-for-activate', [RegistrationController::class, 'waitingForActivate'])->name('auth.waiting_for_activate');

    // logout
    Route::get('/logout', [RegistrationController::class, 'logout'])->name('auth.logout');

    Route::get('/error/{error}', [RegistrationController::class, 'error'])->name('auth.error');

    // api routes
    Route::group(['prefix' => 'api'], function () {

        // login api method
        Route::post('/login', [RegistrationApiController::class, 'login'])->name('auth.api.login');

        // registration api method
        Route::post('/register', [RegistrationApiController::class, 'register'])->name('auth.api.register');

        Route::post('/recovery/check_login', [RegistrationApiController::class, 'checkRecoveryLogin'])->name('auth.api.recovery_check_login');

        // resend verification code api method
        Route::get('/{method}/resend-verification-code', [RegistrationApiController::class, 'resendVerificationCode'])->name('auth.api.resend_verification_code');

        // verification by code api method
        Route::post('/{method}/verify', [RegistrationApiController::class, 'verificationByCode'])->name('auth.api.verification_by_code');

        // set password api method
        Route::post('/{method}/set_password', [RegistrationApiController::class, 'setPassword'])->name('auth.api.set_password');

    });

});

// Add email — залогиненный пользователь, без authorization (тот middleware для pending-регистрации)
Route::group([
    'middleware' => array_merge(
        ['auth'],
        config('user.add_email_middleware', [])
    ),
    'prefix' => config('app.auth_prefix'),
], function () {
    Route::post('/api/add-email/request', [AddEmailController::class, 'requestVerification'])
        ->name('auth.api.add_email.request');
    Route::post('/api/add-email/confirm', [AddEmailController::class, 'confirm'])
        ->name('auth.api.add_email.confirm');
});

Route::group([
    'middleware' => ['auth'],
    'prefix' => config('app.auth_prefix'),
], function () {
    $twoFactor = \Doxa\User\Http\Controllers\TwoFactor\TwoFactorSettingsController::class;
    Route::get('/api/two-factor', [$twoFactor, 'state'])->name('auth.api.two_factor.state');
    Route::post('/api/password', [$twoFactor, 'changePassword'])->name('auth.api.password');
});

Route::group([
    'middleware' => array_merge(
        ['auth'],
        config('user.two_factor_middleware', [])
    ),
    'prefix' => config('app.auth_prefix'),
], function () {
    $twoFactor = \Doxa\User\Http\Controllers\TwoFactor\TwoFactorSettingsController::class;
    Route::post('/api/two-factor/totp/start', [$twoFactor, 'startTotp'])->name('auth.api.two_factor.totp_start');
    Route::post('/api/two-factor/totp/confirm', [$twoFactor, 'confirmTotp'])->name('auth.api.two_factor.totp_confirm');
    Route::post('/api/two-factor/email/start', [$twoFactor, 'startEmail'])->name('auth.api.two_factor.email_start');
    Route::post('/api/two-factor/email/confirm', [$twoFactor, 'confirmEmail'])->name('auth.api.two_factor.email_confirm');
    Route::post('/api/two-factor/disable/start', [$twoFactor, 'startDisable'])->name('auth.api.two_factor.disable_start');
    Route::post('/api/two-factor/disable/confirm', [$twoFactor, 'confirmDisable'])->name('auth.api.two_factor.disable_confirm');
    Route::post('/api/two-factor/backup/start', [$twoFactor, 'startBackup'])->name('auth.api.two_factor.backup_start');
    Route::post('/api/two-factor/backup/confirm', [$twoFactor, 'confirmBackup'])->name('auth.api.two_factor.backup_confirm');
    Route::post('/api/two-factor/backup/remove/start', [$twoFactor, 'startRemoveBackup'])->name('auth.api.two_factor.backup_remove_start');
    Route::post('/api/two-factor/backup/remove/confirm', [$twoFactor, 'confirmRemoveBackup'])->name('auth.api.two_factor.backup_remove_confirm');
    Route::post('/api/two-factor/backup/swap', [$twoFactor, 'swapBackup'])->name('auth.api.two_factor.backup_swap');
    Route::post('/api/two-factor/email-change/start', [$twoFactor, 'startEmailChange'])->name('auth.api.two_factor.email_change_start');
    Route::post('/api/two-factor/email-change/confirm-2fa', [$twoFactor, 'confirmEmailChangeTwoFactor'])->name('auth.api.two_factor.email_change_2fa');
    Route::post('/api/two-factor/email-change/request-new', [$twoFactor, 'requestEmailChangeNew'])->name('auth.api.two_factor.email_change_request');
    Route::post('/api/two-factor/email-change/confirm-new', [$twoFactor, 'confirmEmailChangeNew'])->name('auth.api.two_factor.email_change_confirm');
});
