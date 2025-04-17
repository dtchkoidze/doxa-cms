<?php

namespace Doxa\Modules\Sitemap\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Doxa\Sitemap\Http\Controllers\SitemapController;

/**
 * Undocumented class
 */
class SitemapServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind('sitemap', 'Doxa\Sitemap\Sitemap');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'doxa-sitemap');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        Route::group(['middleware' => ['web', 'public_locale', ]], function () {
            Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
        });
    }
}
