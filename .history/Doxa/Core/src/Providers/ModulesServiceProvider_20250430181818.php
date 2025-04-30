<?php

namespace Doxa\Core\Providers;

use Doxa\Core\Helpers\Doxa;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

/**
 * Class ProjectsServiceProvider represents an entry point
 * for conditional registration of project service providers.
 */
class ModulesServiceProvider extends ServiceProvider
{
    protected $module = '';

    protected $settings;

    protected $package;

    protected $dir_name;

    protected $project_folder;

    protected $doxa_folder;

    protected $project_modules_config_path;

    protected $core_modules_config_path;

    protected $translations;

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        $this->registerStorageDir();

        $this->getPathes();

        if (!file_exists($this->core_modules_config_path)) {
            dd('File ' . $this->core_modules_config_path . ' not found');
        } else {
            //dd('OK ' . $this->core_modules_config_path);
        }

        dd(Config::get('doxa_modules'));

        $project_config = $core_config = [];
        if (file_exists($this->project_modules_config_path)) {
            $project_config_src = include $this->project_modules_config_path;
            dd($project_config_src);
            if (!empty($project_config_src)) {
                foreach ($project_config_src as $key => $val) {
                    if (is_numeric($key)) {
                        $project_config[$val] = [];
                    } else {
                        $project_config[$key] = $val;
                    }
                }
            }
        } else {
            dd('!file_exists(' . $this->project_modules_config_path . ')');
        }

        $core_config_src = include $this->core_modules_config_path;
        foreach ($core_config_src as $key => $val) {
            if (is_numeric($key)) {
                $core_config[$val] = [];
            } else {
                $core_config[$key] = $val;
            }
        }


        if (empty($project_config)) {
            $modules_config = $core_config;
        } else {
            $modules_config = $project_config;
            foreach ($core_config as $name => $settings) {
                if (isset($modules_config[$name])) {
                    $modules_config[$name] = array_merge($modules_config[$name], $settings);
                } else {
                    $modules_config[$name] = $settings;
                }
            }
        }

        //  dd($modules_config);

        // END -- MODULES CONFIGURATION

        foreach ($modules_config as $name => $settings) {

            $this->package = [];

            $this->module = $name;
            $this->settings = $settings;

            $this->configure();
        }

        // dd(Config::get('doxa.modules'));
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->bindsAndDirectives();
    }

    private function registerStorageDir()
    {
        Config::set('filesystems.disks.view_directory', [
            'driver' => 'local',
            'root'   => base_path('packages/Projects/' . config('app.project_name') . '/src/Resources/views'),
        ]);
    }

    private function bindsAndDirectives()
    {
        $this->app->bind('vocab', 'Doxa\Core\Helpers\Vocab');
        Blade::directive('vocab', function ($expression) {
            return "<?php echo app('Doxa\Core\Helpers\Vocab')->get($expression); ?>";
        });

        $this->app->bind('textblock', 'Doxa\Core\Helpers\TextBlock');
        Blade::directive('textblock', function ($expression) {
            return "<?php echo app('Doxa\Core\Helpers\TextBlock')->get($expression); ?>";
        });

        $this->app->bind('pvar', 'Doxa\Core\Helpers\Pvar');
        Blade::directive('pvar', function ($expression) {
            return "<?php echo app('Doxa\Core\Helpers\Pvar')->get($expression); ?>";
        });
    }

    protected function configure()
    {
        $this->dir_name = $repository_class_name = (empty($this->settings['dir_name'])) ? snakeToCamel($this->module, $firstToUppercase = true) : $this->settings['dir_name'];

        $path_to_doxa_module_folder    = Doxa::path_to_doxa_modules() . $this->dir_name;
        $path_to_project_module_folder = base_path($this->project_folder . '/Modules/' . $this->dir_name);

        $path_to_doxa_config    = $path_to_doxa_module_folder . '/config.php';
        $path_to_project_config = $path_to_project_module_folder . '/config.php';

        $path_to_doxa_repository    = $path_to_doxa_module_folder . '/Repositories/' . $repository_class_name . '.php';
        $path_to_project_repository    = $path_to_project_module_folder . '/Repositories/' . $repository_class_name . '.php';

        // dump([
        //     'doxa_exists' => file_exists($path_to_doxa_config),
        //     'project_exists' => file_exists($path_to_project_config),
        //     'project_m_path' => $path_to_project_config,
        //     'module_name' => $this->module,
        // ]);

        file_exists($path_to_doxa_config) && $this->package = include $path_to_doxa_config;
        file_exists($path_to_project_config) && $this->mergeProjectConfig(include $path_to_project_config);


        if(!file_exists($path_to_doxa_config) && !file_exists($path_to_project_config)) {
            //dd($path_to_doxa_config);
            dd($this->module .  'not found');
        }

        if (empty($this->package)) {
            dd($this->module . 'failed');
            return false;
        }

        // dd($this->package);

        //dump('$path_to_doxa_repository: '. $path_to_doxa_repository);

        //dump('$path_to_project_repository: '. $path_to_project_repository);

        if (file_exists($path_to_doxa_repository)) {
            $this->package['paths']['doxa'] = $this->package['paths'] = [
                'dir_path' => $path_to_doxa_module_folder,
                'repository_path' => $path_to_doxa_module_folder . "/Repositories/" . $repository_class_name . '.php',
                'repository_class' =>  $repository_class_name,
                'class' => "\\Doxa\\Modules\\" . $this->dir_name . "\\Repositories\\" . $repository_class_name,
                'class_name' => $repository_class_name
            ];
        }

        // dd($this->package['paths']['doxa']);

        if (file_exists($path_to_project_repository)) {
            //dump('file_exists: '.$path_to_project_repository);
            $this->package['paths']['project'] = $this->package['paths'] = [
                'dir_path' => $path_to_project_module_folder,
                'repository_path' => $path_to_project_module_folder . "/Repositories/" . $repository_class_name . '.php',
                'repository_class' =>  $repository_class_name,
                'class' => "\\Projects\\" . config('app.project_name') . "\\Modules\\" . $this->dir_name . "\\Repositories\\" . $repository_class_name,
                'class_name' => $repository_class_name
            ];
        } else {
            //dump('file NOT exists: '.$path_to_project_repository);
        }

        $this->settings && $this->package = array_replace_recursive($this->package, $this->settings);

        Config::set('doxa.modules.' . $this->module, $this->package);

        // dd(Config::get('doxa.modules.' . $this->module));

        return true;
    }
    private function mergeProjectConfig($project_package_config)
    {
        $this->package = array_merge($this->package, $project_package_config);
    }

    // $core_config_src = require_once dirname(__DIR__).'/Config/modules.php';

    private function getPathes()
    {
        //this->project_folder = 'packages/Projects/' . config('app.project_name') . '/src';
        $this->project_folder = 'packages/Projects/' . config('app.project_name') . '/src';


        $dir = dirname(__DIR__, 2);
        $path = $dir . '/src/Config/modules.php';
        $this->doxa_folder = $dir . '/src';

        //$this->project_modules_config_path = base_path($this->project_folder . '/Config/modules.php');
        $this->project_modules_config_path = base_path('config\modules.php');

        $this->core_modules_config_path = $path;
        

        // dd([
        //     'project_folder' => $this->project_folder,
        //     'doxa_folder' => $this->doxa_folder,
        //     'project_modules_config_path' => $this->project_modules_config_path,
        //     'core_modules_config_path' => $this->core_modules_config_path,
        // ]);
    }
}
