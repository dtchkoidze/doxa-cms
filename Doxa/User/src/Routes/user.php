<?php

use Illuminate\Support\Facades\Route;
use Doxa\User\Http\Controllers\Registration\RegistrationController;
use Doxa\User\Http\Controllers\Registration\ApiController as RegistrationApiController;
use Doxa\User\Http\Controllers\SocialAuth\GoogleController;

// Google OAuth — outside authorization middleware (no pending auth_data / clear on entry)
Route::group(['middleware' => ['web'], 'prefix' => config('app.auth_prefix')], function () {
    Route::get('/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
    Route::get('/google/link', [GoogleController::class, 'linkPage'])->name('auth.google.link');
    Route::post('/google/link/password', [GoogleController::class, 'linkWithPassword'])->name('auth.google.link.password');
    Route::post('/google/link/magic', [GoogleController::class, 'sendMagicLink'])->name('auth.google.link.send_magic');
    Route::get('/google/link/magic/{token}', [GoogleController::class, 'magicLink'])->name('auth.google.link.magic');
    Route::get('/google/link/cancel', [GoogleController::class, 'cancelLink'])->name('auth.google.link.cancel');
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
