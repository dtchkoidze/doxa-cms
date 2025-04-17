<?php

use Illuminate\Support\Facades\Route;
use Doxa\User\Http\Controllers\LoginController;
use Doxa\Admin\Http\Controllers\AdminController;
use Doxa\Admin\Http\Controllers\TinyMCEController;
use Doxa\User\Http\Controllers\RegisterController;
use Doxa\Admin\Http\Controllers\ConfigurationController;

/**
 * Extra routes.
 */

Route::group(['middleware' => ['web'], 'prefix' => config('app.admin_url')], function () {

    // login page
    // Route::get('/login', [LoginController::class, 'create'], )->name('admin.login');
    // // login action|post
    // Route::post('/login', [LoginController::class, 'store'])->name('admin.login.store');

    // // registration page
    // Route::get('/register', [RegisterController::class, 'create'])->name('admin.register');
    // // registration action|post
    // Route::post('/register', [RegisterController::class, 'store'])->name('admin.register.store');

    // // account suspended
    // Route::get('/suspended', [RegisterController::class, 'suspended'])->name('admin.suspended');

    // // verification page
    // Route::get('/verify', [RegisterController::class, 'verify'])->name('admin.verify');

    // // store verification by code
    // Route::post('/verify', [RegisterController::class, 'verificationByCode'])->name('admin.verification.by_code');

    // // verification link
    // Route::get('/verification-link', [RegisterController::class, 'verificationLink'])->name('admin.verification.link');

    // // verification token is wrong
    // Route::get('/wrong-verification-token', [RegisterController::class, 'wrongVerificationToken'])->name('admin.wrong_verification_token');

    // // code expired
    // Route::get('/verification-code-expired', [RegisterController::class, 'verificationCodeExpired'])->name('admin.verification_code_expired');

    // // resend verification code
    // Route::get('/resend-verification-code', [RegisterController::class, 'resendVerificationCode'])->name('admin.resend_verification_code');

    // // admin waiting for activate
    // Route::get('/waiting-for-activate', [RegisterController::class, 'waitingForActivate'])->name('admin.waiting_for_activate');

    // // logout
    // Route::get('/logout', [LoginController::class, 'destroy'])->name('admin.logout');


    Route::group(['middleware' => ['admin']], function () {

        Route::get('/', [AdminController::class, 'dashboard'])->name('admin.home');
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

        Route::get('/no-permissions', [AdminController::class, 'noPermissions'])->name('admin.no_permissions');

        Route::get('/sidebar_menu', [AdminController::class, 'sidebarMenu'])->name('admin.sidebar_menu');

        Route::get('/api/current_user', [AdminController::class, 'currentUser'])->name('admin.current_user');

        Route::post('/api/switch_role', [AdminController::class, 'switchUserRole'])->name('admin.switch_role');

        Route::group(['middleware' => 'superuser'], function () {

            Route::get('/settings/roles', [AdminController::class, 'roles'])->name('admin.settings.roles');

            Route::get('/settings/roles/acl/{id}', [AdminController::class, 'settingsAcl'])->name('admin.settings.acl');

            Route::post('/api/settings/role_permissions/update', [AdminController::class, 'updateRolePermissions'])->name('admin.settings.role_permissions.update');

            Route::get('/api/settings/role_permissions', [AdminController::class, 'getRolePermissions'])->name('admin.settings.get_role_permissions');
        });


        Route::get('/settings/configuration', [ConfigurationController::class, 'index'])->name('admin.settings.configuration');
        Route::prefix('api')->group(function () {
            Route::get('/settings/configuration', [ConfigurationController::class, 'getConfiguration'])
                ->name('admin.settings.get_configuration');
            Route::post('/settings/configuration', [ConfigurationController::class, 'saveConfiguration'])
                ->name('admin.settings.save_configuration');
        });
    });

    /**
     * Tinymce file upload handler.
     */
    Route::post('tinymce/upload', [TinyMCEController::class, 'upload'])->name('admin.tinymce.upload');
});
