<?php

use Illuminate\Support\Facades\Route;
use Doxa\User\Http\Controllers\Auth\AuthSessionController;
use Doxa\User\Http\Controllers\Auth\MobileAuthController;

Route::post(config('user.auth_sessions.mobile_login_path'), [MobileAuthController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::post(config('user.auth_sessions.mobile_logout_path'), [MobileAuthController::class, 'logout']);
    Route::get(config('user.auth_sessions.sessions_list_path'), [AuthSessionController::class, 'index']);
    Route::delete(config('user.auth_sessions.sessions_revoke_path'), [AuthSessionController::class, 'destroy']);
});
