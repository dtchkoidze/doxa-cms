<?php

namespace Doxa\Admin\Libraries\Configuration\Services;

use Illuminate\Support\Facades\DB;

class Parser
{
    private $config;
    private $rows;

    public function __construct()
    {
        $this->config = $this->parse();
    }

    public function parse()
    {
        $config_file_path = base_path('packages/Doxa/Admin/src/Config/configuration.php');
        $project_config_file_path = base_path('packages/Projects/' . config('app.project_name') . '/src/Config/configuration.php');


        $config = [];

        if (file_exists($config_file_path)) {
            $config = require $config_file_path;
            // dd($config);
        }

        if (file_exists($project_config_file_path)) {
            $project_config = require $project_config_file_path;
            $config = array_merge_recursive($config, $project_config);
        }



        if (!empty($config)) {
            $rows = [];
            foreach ($config['blocks'] as $block_name => $block_data) {
                foreach ($block_data['fields'] as $field) {
                    $field['value'] =  '';
                }

                $rows[] = [
                    'name' => strtolower($block_name),
                    'title' => $block_name,
                    'fields' => $block_data['fields'],
                    'subrows' => [],
                ];
            }
        }
        $this->rows = $rows;
        return $rows;
    }

    public function getDBSearchKeys()
    {
        $keys = [];
        foreach ($this->rows as $row) {
            $section_name = strtolower($row['name']);

            foreach ($row['fields'] as $field) {
                $dot_notation_key = $section_name . '.' . $field['key'];
                $keys[] = $dot_notation_key;
            }
        }

        return $keys;
    }

    public function getParsed()
    {
        return $this->config;
    }

    public function getColTypes($cols)
    {
        $col_types = [];
        foreach ($cols as $col) {
            $col_types[$col] = $this->getTypeFromConfig($col);
        }
        return $col_types;
    }
    public function getTypeFromConfig($key, $db = null)
    {
        // dd($key);
        [$sectionName, $fieldKey] = explode('.', $key, 2);

        foreach ($this->rows as $section) {
            if ($section['name'] === $sectionName) {
                foreach ($section['fields'] as $field) {
                    if ($field['key'] === $fieldKey) {
                        if ($db) {
                            return $field['db_type'] ?? null;
                        } else {
                            return $field['type'] ?? null;
                        }
                    }
                }
            }
        }

        return null;
    }
}
