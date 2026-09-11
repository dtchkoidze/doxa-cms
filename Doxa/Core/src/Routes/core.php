<?php

use Doxa\Core\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Маршруты Core: смена локали (словарь — статика public/doxa/dictionary)
|--------------------------------------------------------------------------
*/

Route::get('/doxa/locale/{code}', [LocaleController::class, 'set'])
    ->where('code', '[a-zA-Z]{2,10}')
    ->name('doxa.locale.set');
