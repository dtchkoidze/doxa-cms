<?php

namespace Doxa\Admin\Providers;

use Override;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Doxa\Admin\Http\Middleware\SuperUser;
use Illuminate\Database\Events\QueryExecuted;
use Projects\Dusty\Attributes\OverrideModules;
use Doxa\Admin\Http\Controllers\OmniController;
use Doxa\Admin\Libraries\DBModule\DBModuleManager;
use Doxa\Admin\Libraries\Configuration\Configuration;
use Doxa\Admin\Http\Middleware\Admin as AdminMiddleware;

/**
 * @todo refractoring [10]
 */
class AdminServiceProvider extends ServiceProvider
{
    private $project_folder;

    private $doxa_folder;

    private $project_config_path;

    private $dir_name;

    private $module;

    private $package;

    private $menu = [];

    private $admin_locale;

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Router $router)
    {


        $this->app->booted(function() {
            $schedule = $this->app->make(Schedule::class);

            $schedule->call(new NotConfirmedUsers)->hourly();
            $schedule->call(new ProfilesCleanup)->hourly();
            $schedule->call(new LogsCleanup)->daily();
        });


        $this->getPathes();

        $router->aliasMiddleware('admin', AdminMiddleware::class);

        $router->aliasMiddleware('superuser', SuperUser::class);

        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'admin');

        Blade::anonymousComponentPath(__DIR__ . '/../Resources/views/components', 'admin');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'admin');

        Blade::componentNamespace('Doxa\\Admin\\View\\Components', 'admin');

        $this->binds();

        $this->directives();

        $this->admin_locale = adminLocale();

        $this->resolveModules();

        $this->resolveIndependentLibraries();

        // DB::listen(function (QueryExecuted $query) {
        //     $query->sql;
        //     // $query->bindings;
        //     // $query->time;
        //     $query->toRawSql();
        // });

        // DB::listen(function (QueryExecuted $query) {
        //     $query->sql;
        //     // $query->bindings;
        //     // $query->time;
        //     $query->toRawSql();
        // });


        $this->publishes([
            __DIR__ .'/../Resources/assets/dist'=> public_path('/doxa/admin'),
        ], 'laravel-assets');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register() {}

    private function binds()
    {
        $this->app->bind('omnitranslator', 'Doxa\Admin\Libraries\OmniTranslator');
    }

    private function directives()
    {
        Blade::directive('omniTrans', function ($expression) {
            return "<?php echo app('Doxa\Admin\Libraries\OmniTranslator')->trans($expression); ?>";
        });

        Blade::directive('omniModuleTrans', function ($expression) {
            return "<?php echo app('Doxa\Admin\Libraries\OmniTranslator')->moduleTrans($expression); ?>";
        });

        Blade::directive('omniPageTitle', function ($expression) {
            return "<?php echo app('Doxa\Admin\Libraries\OmniTranslator')->transPageTitle($expression); ?>";
        });
    }

    private function resolveIndependentLibraries()
    {
        // $configuration = app(Configuration::class);
        // $configuration->register();
    }

    private function resolveModules()
    {
        // dd(Config::all());
        $modules = Config::get('doxa.modules');
        foreach ($modules as $key => $package) {
            $this->module = $key;
            $this->package = $package;

            $this->addRoutes();

            $this->dir_name = (empty($this->package['dir_name'])) ? snakeToCamel($this->module, $firstToUppercase = true) : $this->package['dir_name'];

            $this->addTranslations();
            $this->addToMenu();

            $this->processAcl();

            Config::set('doxa.modules.' . $this->module, $this->package);
        }
    }

    protected function addRoutes()
    {
        if (empty($this->package['routes']) || empty($this->package['route'])) {
            $this->addSimpleModuleRoutes();
        }
    }

    protected function addSimpleModuleRoutes()
    {
        //Route::group(['middleware' => ['web', 'admin'], 'prefix' => 'admin'], function () {
        Route::group(['middleware' => ['web', 'admin', 'api'], 'prefix' => config('app.admin_url')], function () {
            Route::controller(OmniController::class)->group(function () {

                Route::get($this->module, 'index')->name('admin.' . $this->module . '.index');

                Route::get($this->module . '/edit/{id}', 'edit')->name('admin.' . $this->module . '.edit');
                Route::post($this->module . '/update/{id}', 'update')->name('admin.' . $this->module . '.update');

                Route::get($this->module . '/copy/{id}', 'copy')->name('admin.' . $this->module . '.copy');

                //Route::get($this->module . '/variation/edit/{id}', 'variation_edit')->name('admin.' . $this->module . '.variation.edit');

                Route::get($this->module . '/create', 'create')->name('admin.' . $this->module . '.create');
                Route::post($this->module . '/create', 'store')->name('admin.' . $this->module . '.store');

                Route::delete($this->module . '/delete/{id}', 'delete')->name('admin.' . $this->module . '.delete');
                Route::delete($this->module . '/delete-variation/{id}', 'deleteVariation')->name('admin.' . $this->module . '.variation.delete');

                Route::get($this->module . '/docs', 'docs')->name('admin.' . $this->module . '.docs');

                Route::patch($this->module . '/row-action', 'rowAction')->name('admin.' . $this->module . '.row_action');
                Route::patch($this->module . '/save-positions', 'savePositions')->name('admin.' . $this->module . '.save_positions');

                Route::get($this->module . '/relation-list',  'getRelationList')->name('admin.' . $this->module . '.relation_list');

                //Route::get($this->module . '/edit_vue/{id}',   'editVue')->name('admin.' . $this->module . '.edit_vue');
                //Route::post($this->module . '/edit_vue/{id}',   'updateVue')->name('admin.' . $this->module . '.update_vue');

                Route::get($this->module . '/get_item_set/{id}', 'getItemSet')->name('admin.' . $this->module . '.getSet');
                Route::get($this->module . '/get_datagrid',  'getDatagrid')->name('admin.' . $this->module . '.getDatagrid');

                Route::post($this->module . '/mass_action',  'massAction')->name('admin.' . $this->module . '.mass_action');

                Route::get($this->module . '/free_action/{id}', 'freeAction')->name('admin.' . $this->module . '.free_action');
            });
        });
    }

    protected function getControllerClass($method_arg)
    {

        $baseOmniController = OmniController::class;

        $childOmniController = 'Projects\\' . config('app.project_name') . '\\Http\\Controllers\\ProjectOmniController';

        $reflection = new \ReflectionClass($childOmniController);

        $overridenMethods = [];

        foreach ($reflection->getMethods() as $method) {
            if (!empty($method->getAttributes(Override::class))) {
                if (in_array($this->module, $method->getAttributes(OverrideModules::class)[0]->getArguments())) {
                    $overridenMethods[] = $method->name;
                }

                $overridenMethods[] = $method->name;
            }
        }

        if (in_array($method_arg, $overridenMethods)) {
            return $childOmniController;
        }


        if (method_exists($baseOmniController, $method_arg)) {
            // dd($baseOmniController);
            return $baseOmniController;
        }
    }

    private function addTranslations()
    {
        $translation = [];

        $doxa_package_translations_file = base_path($this->doxa_folder . '/Modules/' . $this->dir_name . '/lng/' . $this->admin_locale . '.php');
        if (file_exists($doxa_package_translations_file)) {
            $translation = array_merge($translation, require $doxa_package_translations_file);
        }

        $project_package_translation_path = base_path($this->project_folder . '/Modules/' . $this->dir_name . '/lng/' . $this->admin_locale . '.php');
        if (file_exists($project_package_translation_path)) {
            $translation = array_replace_recursive($translation, require $project_package_translation_path);
        }

        $this->package['translations'][$this->admin_locale] = $translation;

        Config::set('doxa.modules.' . $this->module, $this->package);
    }

    protected function addToMenu()
    {
        if (!empty($this->package['menu'])) {
            if (!empty($this->package['translations'][$this->admin_locale]['index']['title'])) {
                $this->package['menu']['name'] = $this->package['translations'][$this->admin_locale]['index']['title'];
            }
            $this->package['menu']['description'] = omniModuleTrans($this->module, 'index', 'description');
            Config::set('admin.menu.' . $this->module, $this->package['menu']);
        }
    }

    protected function processAcl()
    {
        static $cumulativeAcl = [];

        if (!empty($this->package['acl'])) {
            $acl = array_merge(config('admin.acl'), $this->package['acl']);
        } else {
            $acl = $this->generateDefaultAcl();
        }

        $cumulativeAcl = array_merge($cumulativeAcl, $acl);
        $cumulativeAcl = array_unique($cumulativeAcl, SORT_REGULAR);

        Config::set('admin.acl', $cumulativeAcl);
    }
    protected function generateDefaultAcl()
    {
        $sortIndex = 0;
        $defaultAcl = [
            [
                'key' => $this->module,
                'name' => ucfirst($this->module),
                'route' => 'admin.' . $this->module . '.index',
                'sort' => $sortIndex++,
            ],
        ];

        $defaultAclActions = [
            'create',
            'store',
            'update',
            'edit',
            'delete',
            'docs',
            'deleteVariation',
            'rowAction',
            'savePositions',
        ];

        foreach ($defaultAclActions as $action) {
            $defaultAcl[] = [
                'key' => $this->module . '.' . $action,
                'name' => ucfirst($action),
                'route' => 'admin.' . $this->module . '.' . $action,
                'sort' => 1,
            ];
        }

        return $defaultAcl;
    }


    private function getPathes()
    {
        $this->project_folder = 'packages/Projects/' . config('app.project_name') . '/src';
        $this->doxa_folder = 'packages/Doxa';
        $this->project_config_path = base_path($this->project_folder . '/config/packages.php');
    }
}
