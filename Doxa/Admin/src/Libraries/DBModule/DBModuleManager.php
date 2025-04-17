<?php

namespace Doxa\Admin\Libraries\DBModule;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class DBModuleManager
{
    private $module_name;
    private $table_name;
    private $module_name_snake;
    private $config;
    private $errors = [];
    private $info = [];
    private $backup_table_name = '';

    public function __construct(private ModuleTypes $types) {}


    public function updateDBTableFromConfig(string $module_name): array
    {
        $actions = [];
        $this->module_name = $module_name;
        $this->module_name_snake = Str::snake($module_name);
        $module_config_file = '';
        $module_core_path = base_path('packages/Doxa/Modules/' . $module_name . '/config.php');
        $module_project_path = base_path('packages/Projects/' . config('app.project_name') .  '/src/Modules/' . $module_name . '/config.php');

        if (file_exists($module_core_path)) {
            $module_config_file = $module_core_path;
        } elseif (file_exists($module_project_path)) {
            $module_config_file = $module_project_path;
        } else {
            return ['success' => false, 'errors' => ["Module config file does not exist"]];
        }

        $this->config = require $module_config_file;
        $config_scheme = $this->config['scheme'];
        $this->table_name = isset($config_scheme['table']['name']) ? $config_scheme['table']['name'] : null;
        $fields = isset($config_scheme['table']['fields']) ? $config_scheme['table']['fields'] : null;

        if (is_null($this->table_name)) {
            return ['success' => false, 'errors' => ["Table name is not set in config file"]];
        } else {
            if (!Schema::hasTable($this->table_name)) {
                $actions[] = ['create' => 'table'];
            } else {
                $actions[] = ['update' => 'table'];
            }
        }

        if (count($actions) < 1) {
            return ['success' => true, 'info' => $this->info];
        }

        foreach ($actions as $action) {
            $method_name = key($action) . ucfirst(reset($action));
            $method_name = Str::camel($method_name);
            if (method_exists($this, $method_name)) {
                $res = $this->$method_name('project');
                $res ?: $this->errors[] = "Method $method_name failed";
            } else {
                $this->errors[] = "Method $method_name does not exist";
            }
        }

        if (count($this->errors) > 0) {
            return ['success' => false, 'errors' => $this->errors];
        }

        return ['success' => true, 'info' => $this->info];
    }


    private function createTable(): bool
    {

        $sql = "CREATE TABLE $this->table_name (id INT AUTO_INCREMENT PRIMARY KEY)";

        $res = DB::statement($sql);

        if (!$res) {
            $this->errors[] = "Table $this->table_name could not be created";
        }

        $fields = $this->config['scheme']['table']['fields'];
        $indexes = $this->config['scheme']['table']['indexes'] ?? null;

        foreach ($fields as $field_name => $params) {
            if ($field_name === 'id') {
                continue;
            }

            $alias = $params['alias'] ?? null;
            $laravel_alias = $params['laravel_alias'] ?? null;
            $laravel_chain = $params['laravel_chain'] ?? null;


            if ($alias) {
                dump("alias found: ", $alias);
                $res = $this->createColumnFromAlias($field_name, $alias);
                if (!$res) {
                    $this->errors[] = "Column $field_name could not be created from alias";
                }
            } else if ($laravel_alias) {
                $res = $this->createColumnFromLaravelAlias($field_name, $laravel_alias, $laravel_chain);
                if (!$res) {
                    $this->errors[] = "Column $field_name could not be created from laravel alias";
                }
            } else {
                $res = $this->createColumnFromRawData($field_name, $params);
                if (!$res) {
                    $this->errors[] = "Column $field_name could not be created from raw data";
                }

                if (!is_null($indexes)) {
                    $res = $this->createIndexes($indexes);
                    if (!$res) {
                        $this->errors[] = "Indexes $indexes could not be created";
                    }
                }
            }
        }

        if (count($this->errors) < 1) {
            return true;
        } else {
            dd($this->errors);
            return false;
        }
    }

    /**
     * Create column from laravel alias
     * chain is an array of methods and their optional arguments
     * is written with very same syntax as laravel schema builder
     * @param string $name
     * @param string $alias
     * @param array $chain
     * @return bool
     */
    private function createColumnFromLaravelAlias($name, $alias, $chain): bool
    {
        try {
            Schema::table($this->table_name, function ($table) use ($name, $alias, $chain) {
                $column = $table->$alias($name);

                if ($chain) {
                    foreach ($chain as $method => $args) {
                        if (empty($args)) {
                            $column->$method();
                        } else {
                            $column->$method(...$args);
                        }
                    }
                }
            });

            return true;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }



    /**
     * Create column from alias
     * Alias is a string that is resolved to array of column parameters
     * Column parameters follow the style of sql column defs
     * @param string $name
     * @param string $alias
     * @return bool
     */
    private function createColumnFromAlias($name, $alias)
    {
        $params = $this->types->resolveAlias($alias);

        if (!$params) {
            return false;
        }

        $sql_parts = $this->concatSqlStringFromAssocSqlData($params);

        $sql = "ALTER TABLE `$this->table_name` ADD COLUMN `$name` " . implode(' ', $sql_parts);

        try {
            DB::statement($sql);
            return true;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    private function createColumnFromRawData($name, $data)
    {
        if (!$data || empty($data['type'])) {
            return false;
        }

        try {
            Schema::table($this->table_name, function (Blueprint $table) use ($name, $data) {
                $laravel_alias = $this->types->getSQLToLaravelType($data['type_name']);
                if (method_exists($table, $laravel_alias)) {
                    $column = $table->$laravel_alias($name);
                } else {
                    dd("Method $laravel_alias does not exist");
                }

                if (isset($data['nullable']) && !empty($data['nullable'])) {
                    $column->nullable();
                }

                if (isset($data['default']) && $data['default'] !== null && strtoupper($data['default']) !== 'NULL') {
                    $column->default($data['default']);
                }

                if (isset($data['auto_increment']) && !empty($data['auto_increment'])) {
                    $column->autoIncrement();
                }

                if (isset($data['collation']) && !empty($data['collation'])) {
                    $column->collation($data['collation']);
                }

                if (isset($data['comment']) && !empty($data['comment'])) {
                    $column->comment($data['comment']);
                }
            });

            return true;
        } catch (\Exception $e) {
            $this->errors[] = "Error adding column $name: " . $e->getMessage();
            return false;
        }
    }


    private function concatSqlStringFromAssocSqlData($data): array|bool
    {
        if (!$data) {
            return false;
        }

        $sql_parts = [];

        if (!empty($data['type'])) {
            $type = strtoupper($data['type']);

            if (in_array($type, ['VARCHAR', 'CHAR']) && !empty($data['length'])) {
                $type .= "({$data['length']})";
            }

            $sql_parts[] = $type;
        }

        $is_nullable = !empty($data['is_nullable']) ? 'NULL' : 'NOT NULL';
        $sql_parts[] = $is_nullable;

        if (array_key_exists('default_value', $data)) {
            $default_value = $data['default_value'];

            if ($default_value === null && $is_nullable === 'NULL') {
                $sql_parts[] = 'DEFAULT NULL';
            } elseif ($default_value !== null) {
                $sql_parts[] = "DEFAULT '" . addslashes($default_value) . "'";
            }
        }

        if (!empty($data['extra']) && strtolower($data['extra']) === 'auto_increment') {
            $sql_parts[] = 'AUTO_INCREMENT';
        }

        return $sql_parts;
    }


    private function updateTable()
    {
        $db_data = $this->getDataFromDBTable2();
        $fields_from_db = $db_data['scheme']['table']['fields'];
        $fields_from_config = $this->config['scheme']['table']['fields'];
        $laravel_aliases = [];


        foreach ($fields_from_config as $field_name => $params) {
            if (isset($params['laravel_alias'])) {
                $laravel_aliases[$field_name] = $params['laravel_alias'];
                if (isset($params['laravel_chain'])) {
                    $laravel_aliases['chain'] = $params['laravel_chain'];
                }
            }
        }



        unset($fields_from_db['id'], $fields_from_config['id']);



        if ($fields_from_db == $fields_from_config) {
            dump("no changes");
            $this->info[] = "No changes detected between configuration and db table";
            return true;
        }

        $diff = $this->arrayRecursiveDiff($fields_from_config, $fields_from_db);
        foreach ($diff as $key => $value) {
            // dump($fields_from_config[$key]);
            // dump($fields_from_db[$key]);
        }


        if ($diff) {
            $this->versionAndBackUpTable();


            foreach ($diff as $key => $val) {
                $column_name = $key;
                $changed_params = $val;

                $this->updateDbColumn($column_name, $changed_params);
            }



            if (count($this->errors) > 0) {
                $this->rollbackBackUpTable();
                dd($this->errors);
                return false;
            } else {
                dd("Table updated successfully");
                return true;
            }
        }

        return true;
    }


    private function versionAndBackUpTable()
    {
        $version = time();
        $backup_table_name = $this->table_name . "_backup_$version";
        $this->backup_table_name = $backup_table_name;

        $sql = "CREATE TABLE $backup_table_name AS SELECT * FROM $this->table_name";

        try {
            DB::statement($sql);
            return true;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->backup_table_name  = '';
            return false;
        }
    }

    private function rollbackBackUpTable()
    {
        if (!empty($this->backup_table_name)) {
            $sql = "DROP TABLE $this->backup_table_name";
            try {
                DB::statement($sql);
                return true;
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
                return false;
            }
        }
    }

    private function updateDbColumn($column_name, $changed_params) {}



    private function arrayRecursiveDiff($aArray1, $aArray2)
    {
        $aReturn = [];

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) {
                        $aReturn[$mKey] = $aRecursiveDiff;
                    }
                } else {
                    if ($mValue !== $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }

        foreach ($aArray2 as $mKey => $mValue) {
            if (!array_key_exists($mKey, $aArray1)) {
                $aReturn[$mKey] = $mValue;
            }
        }

        return $aReturn;
    }


    private function createIndex($field_name, $index)
    {
        $index_name = $index['name'];
        $columns = is_array($index['column_name']) ? implode('`, `', $index['column_name']) : $index['column_name'];
        $non_unique = $index['non_unique'];

        $sql = "ALTER TABLE `$this->table_name`";

        if ($index_name === 'PRIMARY') {
            $sql .= " ADD PRIMARY KEY (`$columns`)";
        } elseif (!empty($index['type']) && strtoupper($index['type']) === 'FULLTEXT') {
            $sql .= " ADD FULLTEXT `$index_name` (`$columns`)";
        } elseif (!empty($index['type']) && strtoupper($index['type']) === 'SPATIAL') {
            $sql .= " ADD SPATIAL INDEX `$index_name` (`$columns`)";
        } elseif ($non_unique == 0) {
            $sql .= " ADD UNIQUE `$index_name` (`$columns`)";
        } else {
            $sql .= " ADD INDEX `$index_name` (`$columns`)";
        }

        try {
            DB::statement($sql);
            return true;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    private function createIndexes($indexes)
    {
        return true;
    }

    /**
     *
     */
    public function checkConfigFromDBTable(string $table_name, string $module_name): array
    {
        $this->module_name = $module_name;
        $this->module_name_snake = Str::snake($module_name);
        $this->table_name = $table_name;

        $module_exists = false;
        $table_exists = false;
        $module_config_file_exists = false;
        $module_config_file = null;
        $module_not_active = false;
        $actions = [];
        $errors = [];
        $info = [];

        $module_core_path = base_path('packages/Doxa/Modules/' . $module_name . '/config.php');
        $module_project_path = base_path('packages/Projects/' . config('app.project_name') .  '/src/Modules/' . $module_name . '/config.php');
        $doxa_modules = Config::get('doxa.modules');


        if (Schema::hasTable($table_name)) {
            $table_exists = true;
        } else {
            $errors[] = "Table $table_name does not exist";
            return ['success' => false, 'errors' => $errors];
        }


        if (array_key_exists($this->module_name_snake, $doxa_modules)) {
            $module_exists = true;
            $module_config_file_exists = true;
            $module_config_file = $doxa_modules[$this->module_name_snake]['paths']['dir_path'] . '/config.php';
            $actions[] = ['check' => 'config'];
        }

        if (!$module_exists) {
            if ($module_config_file = file_exists($module_core_path) || $module_config_file = file_exists($module_project_path)) {
                $module_config_file_exists = true;
                $module_not_active = true;
                $info[] = "Module: $module_name config exists but is not written in modules.php file";
            } else {
                $actions[] = ['create' => 'config'];
            }
        }

        if (count($actions) < 1) {
            return ['success' => true, 'info' => $info];
        }

        foreach ($actions as $action) {
            $method_name = key($action) . ucfirst(reset($action));
            $method_name = Str::camel($method_name);
            if (method_exists($this, $method_name)) {
                $res = $this->$method_name('project');
                $res ?: $errors[] = "Method $method_name failed";
            } else {
                $errors[] = "Method $method_name does not exist";
            }
        }


        if (count($errors) > 0) {
            return ['success' => false, 'errors' => $errors];
        }

        return ['success' => true, 'info' => $info];
    }


    /**
     * NOT IN USE
     * Activate module, write name in modules.php file dessit
     * @param string $dir full path, as in config(doxa.modules) paths dir_path
     * @return void
     */
    private function activateModule($dir = 'project')
    {
        if ($dir == 'project') {
            $module_path = base_path('packages/Projects/' . config('app.project_name') . '/src/Config/modules.php');
        }

        if (!is_dir(dirname($module_path))) {
            mkdir(dirname($module_path), 0755, true);
        }

        if (!file_exists($module_path)) {
            file_put_contents($module_path, "<?php\n\nreturn [\n\n];\n");
        }

        $module_config = require $module_path;

        if (!is_array($module_config)) {
            $module_config = [];
        }

        $name_for_config = Str::snake($this->module_name);
        if (!in_array($name_for_config, $module_config)) {
            $module_config[] = $name_for_config;
        } else {
            return true;
        }

        $content = "<?php\n\nreturn [\n";

        foreach ($module_config as $module) {
            $content .= "    '$module',\n";
        }
        $content .= "];\n";

        $res = file_put_contents($module_path, $content);

        return $res;
    }

    private function createConfig()
    {
        $path = base_path('packages/Projects/' . config('app.project_name') . '/src/Modules/' . $this->module_name . '/config.php');
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (!file_exists($path)) {
            file_put_contents($path, "");
        }

        $data = $this->getDataFromDBTable2();
        $res = $this->writeInConfigFile($path, $data);
        return $res;
    }

    private function checkConfig()
    {
        return true;
    }

    private function getDataFromDBTable()
    {

        $database = config('database.connections.mysql.database');
        $data = [];
        $columns = DB::select(
            "SELECT
                COLUMN_NAME as name,
                DATA_TYPE as type,
                CHARACTER_MAXIMUM_LENGTH as length,
                NUMERIC_PRECISION as numeric_precision,
                NUMERIC_SCALE as numeric_scale,
                IS_NULLABLE as is_nullable,
                COLUMN_DEFAULT as default_value,
                EXTRA as extra
            FROM
                INFORMATION_SCHEMA.COLUMNS
            WHERE
                TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
            ORDER BY
                ORDINAL_POSITION",
            [$database, $this->table_name]
        );

        $indexes = DB::select(
            "SELECT
                INDEX_NAME as name,
                COLUMN_NAME as column_name,
                NON_UNIQUE as non_unique
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            ORDER BY INDEX_NAME, SEQ_IN_INDEX",
            [$database, $this->table_name]
        );

        $seg_1 = [
            'module' => $this->module_name_snake,
            'title' => Str::title($this->module_name),
            'title_plural' => Str::plural(Str::title($this->module_name)),
        ];

        $scheme = [
            'table' => [
                'name' => $this->table_name,
                'fields' => [],
            ],
        ];

        foreach ($columns as $col) {
            $alias = $this->compute_alias($col);
            foreach ($indexes as $index) {
                $scheme['table']['fields'][$col->name] =
                    $alias

                    ?

                    [
                        'alias' => $alias,
                        'index' => $index->column_name == $col->name ? collect($index)->toArray() : null,
                    ]

                    :

                    [
                        'type' => $col->type,
                        'length' => is_null($col->length) ? null : $col->length,
                        'numeric_precision' => is_null($col->numeric_precision) ? null : $col->numeric_precision,
                        'numeric_scale' => is_null($col->numeric_scale) ? null : $col->numeric_scale,
                        'is_nullable' => $col->is_nullable,
                        'default_value' => $col->default_value,
                        'extra' => $col->extra,
                        'index' => $index->column_name == $col->name ? collect($index)->toArray() : null,
                    ];
            }
        }


        return [
            'seg_1' => $seg_1,
            'scheme' => $scheme,
        ];
    }

    private function getDataFromDBTable2()
    {

        $columns = Schema::getColumns($this->table_name);
        $indexes = Schema::getIndexes($this->table_name);


        $seg_1 = [
            'module' => $this->module_name_snake,
            'title' => Str::title($this->module_name),
            'title_plural' => Str::plural(Str::title($this->module_name)),
        ];

        $scheme = [
            'table' => [
                'name' => $this->table_name,
                'fields' => [],
            ],
        ];

        foreach ($columns as $col) {
            if (!isset($scheme['table']['fields'][$col['name']])) {
                $scheme['table']['fields'][$col['name']] = [];
            }

            foreach ($col as $key => $val) {
                if ($key == 'name') {
                    continue;
                }

                $scheme['table']['fields'][$col['name']] = array_merge($scheme['table']['fields'][$col['name']], [$key => $val]);
                if (!empty($indexes)) {
                    $scheme['table']['indexes'] = $indexes;
                }
            }
        }


        return [
            'seg_1' => $seg_1,
            'scheme' => $scheme,
        ];
    }


    private function writeInConfigFile($path, $data)
    {
        $seg_1 = $data['seg_1'];
        $scheme = $data['scheme'];
        $fields = $scheme['table']['fields'];
        $indexes = $scheme['table']['indexes'];
        $content = "<?php\n\nreturn [\n";

        foreach ($seg_1 as $key => $value) {
            $content .= "    '$key' => '$value',\n";
        }

        $content .= "    'scheme' => [\n";
        $content .= "        'table' => [\n";
        $content .= "            'name' => '{$scheme['table']['name']}',\n";

        $content .= "            'fields' => [\n";
        foreach ($fields as $field_name => $params) {
            $content .= "                '$field_name' => [\n";
            foreach ($params as $key => $value) {
                if ($value === null) {
                    $value_str = 'null';
                } else if ($value === false) {
                    $value_str = 'false';
                } else if ($value === true) {
                    $value_str = 'true';
                } else if ($value === "''") {
                    $value_str  = "''";
                } else if ($value === '') {
                    $value_str = "''";
                } else if ($value === 0) {
                    $value_str = '0';
                } else if ($value === "0") {
                    $value_str = "'0'";
                } else {
                    $value_str = is_numeric($value) ? $value : "'$value'";
                }
                $content .= "                    '$key' => $value_str,\n";
            }
            $content .= "                ],\n";
        }
        $content .= "            ],\n";

        $content .= "            'indexes' => [\n";
        foreach ($indexes as $index) {
            $content .= "                [\n";
            foreach ($index as $key => $value) {
                if ($key === 'columns') {
                    $columns = implode("', '", $value);
                    $content .= "                    '$key' => ['$columns'],\n";
                } else {
                    $value_str = is_bool($value) ? ($value ? 'true' : 'false') : (is_numeric($value) ? $value : "'$value'");
                    $content .= "                    '$key' => $value_str,\n";
                }
            }
            $content .= "                ],\n";
        }
        $content .= "            ],\n";

        $content .= "        ],\n";
        $content .= "    ],\n";
        $content .= "];\n";

        return file_put_contents($path, $content);
    }


    private function compute_alias($col)
    {
        $alias = $this->types->getAlias($col);

        if (!empty($alias)) {
            return $alias;
        } else {
            return null;
        }
    }
}
