<?php

namespace Doxa\Core\Providers;

use Doxa\Core\Console\Commands\DictionaryBuildCommand;
use Doxa\Core\Console\Commands\DictionaryScanCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Core: локаль, команды словаря Doxa, маршрут /doxa/locale.
 * JSON словаря — статика хоста public/doxa/dictionary (не через контроллер).
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует конфиги локали и словаря; artisan-команды словаря.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/locale.php',
            'doxa.locale'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/dictionary.php',
            'doxa.dictionary'
        );

        $this->commands([
            DictionaryScanCommand::class,
            DictionaryBuildCommand::class,
        ]);
    }

    /**
     * Подключает маршруты Core (смена локали).
     */
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__ . '/../Routes/core.php');
    }
}
