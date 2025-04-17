<?php

namespace Doxa\User\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'user');

        Blade::anonymousComponentPath(__DIR__ . '/../Resources/views/components', 'user');

        Route::middleware('web')->group(__DIR__ . '/../Routes/user.php');

        $router->aliasMiddleware('user', \Doxa\User\Http\Middleware\User::class);
        $router->aliasMiddleware('authorization', \Doxa\User\Http\Middleware\Authorization::class);

        $this->publishes([
            __DIR__ . '/../Resources/assets/dist' => public_path('/doxa/user'),
        ], 'laravel-assets');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //$this->registerBouncer();
    }

    /**
     * Register Bouncer as a singleton.
     *
     * @return void
     */
    // protected function registerBouncer()
    // {
    //     $loader = AliasLoader::getInstance();
    //     $loader->alias('Bouncer', BouncerFacade::class);

    //     $this->app->singleton('bouncer', function () {
    //         return new Bouncer();
    //     });
    // }
}
