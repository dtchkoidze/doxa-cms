<?php

namespace Doxa\Modules\Sitemap\Http\Controllers;


use Doxa\Core\Libraries\Chlo;
use Doxa\Sitemap\Facades\Sitemap;
use Illuminate\Routing\Controller;

class SitemapController extends Controller
{
    /**
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $path = base_path() . '/packages/Projects/' . config('app.project_name') . '/src/Files/sitemap/' . Chlo::getCurrentChannelCode() . '/sitemap.xml';

        if (request('action') == 'generate' || !file_exists($path)) {
            Sitemap::generate();
        }
        return response(file_get_contents($path), 200)->header('Content-Type', 'application/xml');
    }
}
