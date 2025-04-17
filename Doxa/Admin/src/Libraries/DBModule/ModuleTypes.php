<?php

namespace Doxa\Admin\Libraries\DBModule;


class ModuleTypes
{

    private $module_types = [
        'int',
        'bigint',
        'tinyint',
        'text',
        'longtext',
        'varchar',
        'decimal',
        'timestamp',
    ];

    private $sql_to_laravel_types = [
        'int'       => 'integer',
        'bigint'    => 'bigInteger',
        'smallint'  => 'smallInteger',
        'tinyint'   => 'tinyInteger',
        'decimal'   => 'decimal',
        'numeric'   => 'decimal',
        'float'     => 'float',
        'double'    => 'double',
        'char'      => 'char',
        'varchar'   => 'string',
        'text'      => 'text',
        'longtext'  => 'longText',
        'mediumtext' => 'mediumText',
        'blob'      => 'binary',
        'boolean'   => 'boolean',
        'datetime'  => 'dateTime',
        'timestamp' => 'timestamp',
        'date'      => 'date',
        'time'      => 'time',
    ];


    // todo: type aliases based on laravel migration type names e.g string => varchar, 191;
    /**
     * These types refer to types written in config files that are used in admin
     */
    private $corresponding_types_for_editing = [];

    public function getModuleTypes()
    {
        return $this->module_types;
    }


    public function checkForTypeInconsistency($type)
    {
        if (!in_array($type, $this->module_types)) {
            return false;
        }

        if (isset($this->corresponding_types_for_editing[$type])) {
            return $this->corresponding_types_for_editing[$type];
        }
    }

    private function getAliases()
    {
        $conf_path = base_path('packages/Doxa/Admin/src/Config/types.php');
        $config =  require $conf_path;
        $aliases = $config['aliases'];
        return $aliases;
    }

    public function getAlias($col_params)
    {
        $aliases = $this->getAliases();
        $colume_name = $col_params->name;

        if (in_array($colume_name, array_keys($aliases))) {
            return $colume_name;
        } else {
            return null;
        }
    }

    public function resolveAlias($alias)
    {
        $aliases = $this->getAliases();
        if (in_array($alias, array_keys($aliases))) {
            return $aliases[$alias];
        } else {
            return null;
        }
    }

    public function getSQLToLaravelTypes()
    {
        return $this->sql_to_laravel_types;
    }

    public function getSQLToLaravelType($sql_type)
    {
        $sql_to_laravel_types = $this->getSQLToLaravelTypes();
        return $sql_to_laravel_types[$sql_type];
    }
}
