<?php

namespace Doxa\Admin\Http\Controllers;

use Doxa\Admin\Libraries\Configuration\Configuration;

class ConfigurationController
{

    public function index()
    {
        return view('admin::settings.configuration.index');
    }

    public function getConfiguration()
    {
        $c = app(Configuration::class);
        $configuration = $c->getConfiguration();

        return response()->json([
            'data' => $configuration,
            'status' => 'success',
        ]);
    }

    public function saveConfiguration()
    {
        $p_original = request()->all();
        $payload = [];

        foreach ($p_original as $key => $val) {
            $first_underscore = strpos($key, "_");

            if ($first_underscore !== false) {
                $new_key = substr_replace($key, '.', $first_underscore, 1);
                $payload[$new_key] = $val;
            } else {
                $payload[$key] = $val;
            }
        }

        $c = app(Configuration::class);
        $saved = $c->saveConfiguration($payload);

        if ($saved) {
            return response()->json([
                'status' => 'success',
                'message' => 'Configuration saved successfully',
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save configuration',
            ]);
        }
    }
}
