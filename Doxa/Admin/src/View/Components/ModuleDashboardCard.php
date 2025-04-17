<?php

namespace Doxa\Admin\View\Components;

use Closure;

use Illuminate\View\Component;
use Doxa\Admin\Facades\Package;
use Doxa\Core\Helpers\Logging\Clog;
use Illuminate\Contracts\View\View;

class ModuleDashboardCard extends Component
{
    
    public function __construct(
        protected string $module
    )
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        $re = [
            'module' => $this->module,
            'title' => omniModuleTrans($this->module, 'index', 'title'),
            'description' => omniModuleTrans($this->module, 'index', 'description'),
            'icon' => config('doxa.package.'.$this->module.'.menu.icon'),
        ];

        return view('doxa-admin::components.dashboard.package_card', $re);
    }


}
