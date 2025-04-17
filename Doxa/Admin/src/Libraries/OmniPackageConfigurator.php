<?php

namespace Doxa\Admin\Libraries;

use Closure;

use Doxa\Admin\Repositories\AdminRepository;
use Doxa\Core\Libraries\Chlo;
use Doxa\Core\Helpers\Logging\Clog;
use Doxa\Core\Libraries\Package\Package;

trait OmniPackageConfigurator
{
    public function configure($method, $args = [])
    {

        $this->method = $method;

        $this->module = request()->segments()[1];

        $this->package = new Package($this->module, $this->method);

        //Chlo::initForPackage($this->package);

        Chlo::init();

        if($this->package->isChannelsIgnored()){
            Chlo::withoutChannels();
        }

        //Chlo::init($this->module);

        // if(request('admin')){
        //     $this->repository = new AdminRepository($this->package);
        // } else {
        //     $this->repository = moduleRepository($this->module);
        // }

        $this->repository = moduleRepository($this->module);

        $this->getDataGridClass();

        $this->getView();
    }

    private function getDataGridClass()
    {
        $this->datagridClass = 'Doxa\\Admin\\Libraries\\DoxaDataGrid';
    }

    private function getView()
    {
        $this->view = 'admin::omni.' . $this->getViewName($this->method);
    }

    private function getViewName($method)
    {
        return 'index';
    }

}
