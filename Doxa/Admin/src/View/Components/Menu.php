<?php

namespace Doxa\Admin\View\Components;

use Illuminate\View\Component;
use Doxa\Admin\Facades\Package;
use Doxa\Core\Helpers\Logging\Clog;
use Illuminate\Contracts\View\View;

class Menu extends Component
{
    
    public function __construct()
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('admin::components.menu', ['menu' => config('admin.menu')]);
    }


}
