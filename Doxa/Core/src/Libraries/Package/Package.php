<?php

namespace Doxa\Core\Libraries\Package;

use Illuminate\Support\Collection;

/**
 * @method this isEditingFieldExists($key, $type = 'base') if editing field exists in package
 */
class Package
{
    use Datagrid, Editing, Permissions;

    public string $module;

    protected Collection $config;

    public string $name;

    public object $scheme;

    public Collection $columns;

    protected string $method = '';

    public $cache_required = false;

    public function __construct($module, $method = null)
    {

        $this->module = $module;

        $this->config = collect(config('doxa.modules.' . $module));


        if (empty($this->config)) {
            die('Package ' . $module . ' not found or not configured correctly for project');
        }

        $this->getPackageName();

        $this->scheme = new Scheme($this->config->get('scheme'));

        // dump($this->module, $this->config);
        if(empty($this->config->get('editing'))){
            dump($this->scheme);
            dd($this->module);
        }

        // dump($this->config);
        $this->columns = collect($this->config->get('editing')['columns'])->mapInto(Column::class);

        $this->getEditingFieldsInfo();
    }

    public function getPackageName()
    {
        if (!empty($this->config['package_name'])) {
            $this->name = $this->config['package_name'];
        } else {
            $this->name = '';
            $a = explode('_', $this->module);
            foreach ($a as $s) {
                $this->name .= ucfirst($s);
            }
        }
        return $this->name;
    }

    public function defaultChannelOnly()
    {
        return $this->config->get('default_channel_only') ? true : false;
    }

    public function isChannelsIgnored()
    {
        return $this->config->get('ignore_channels') ? true : false;
    }

    public function hasVariations()
    {
        return $this->scheme->getVariationsTable();
    }

    public function isSimpleModule()
    {
        return !empty($this->config['simple-module']);
    }

    public function massActionEnabled()
    {
        return true;
    }

    public function getPackageFolders()
    {
        $folders = [];
        $this->config->get('project_package_dir') && $folders[] = $this->config->get('project_package_dir');
        $this->config->get('doxa_package_dir') && $folders[] = $this->config->get('doxa_package_dir');
        return $folders;
    }

    public function getPaths()
    {
        return $this->config->get('paths');
    }

    private function getCustomClass()
    {
        if (!empty($this->config['package_name'])) {
            $this->name = $this->config['package_name'];
        } else {
            $this->name = '';
            $a = explode('_', $this->module);
            foreach ($a as $s) {
                $this->name .= ucfirst($s);
            }
        }
    }

    public function isHistoryEnabled()
    {
        return !empty($this->config['history']) && $this->config['history'] == true;
    }






    public function useOmniView()
    {
        return $this->useOmni('view');
    }

    private function useOmni($src)
    {

        if ($this->method) {

            if (isset($this->config['methods'][$this->method]['omni_' . $src])) {
                return (bool) $this->config['methods'][$this->method]['omni_' . $src];
            }

            if (!empty($this->config['methods'][$this->method]['fully_omni'])) {
                return true;
            }
        }

        if (!empty($this->config['fully_omni'])) {
            return true;
        }

        if (!empty($this->config['omni_' . $src])) {
            return true;
        }

        return false;
    }

    public function relatedListFields()
    {
        if (isset($this->config['related_list']['fields'])) {
            return $this->config['related_list']['fields'];
        }
        return false;
    }

    public function relatedListOrder()
    {
        if (isset($this->config['related_list']['order'])) {
            return $this->config['related_list']['order'];
        }
        return false;
    }


    //C:\xampp8\htdocs\admin.loc\packages\Doxa\Feedback\Libraries\Feedback.php
    //C:\xampp8\htdocs\admin.loc\packages\Doxa\Feedback\Libraries\Feedback.php

}
