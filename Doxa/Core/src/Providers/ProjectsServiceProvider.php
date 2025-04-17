<?php

namespace Doxa\Core\Providers;

use Doxa\Core\Libraries\Chlo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;


class ProjectsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Chlo::init();
        // $allowed_hosts = [];
        // $channels = Chlo::asAssocById()['channels'];

        // for ($i = 1; $i <= count($channels); $i++) {
        //     $allowed_hosts[] = $channels[$i]->host;
        // }

        // $requested_host = request()->getHost();
        // if (in_array($requested_host, $allowed_hosts, true)) {
        //     Config::set('app.url', request()->getSchemeAndHttpHost());
        // }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register() {}
}
