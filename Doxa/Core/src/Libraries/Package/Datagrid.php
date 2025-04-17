<?php

namespace Doxa\Core\Libraries\Package;

trait Datagrid
{

    protected $datagrid;

    protected $per_page_default = 10;

    public function getDatagridHandler()
    {
        return !empty($this->config['datagrid']['handler']) ? $this->config['datagrid']['handler'] : 'laravel';
    }

    public function getDatagridColumns()
    {
        $_columns = [];
        if(!empty($this->config->get('datagrid')['columns'])){
            foreach($this->config->get('datagrid')['columns'] as $key => &$column){
                if(is_string($column)){
                    if( config('doxa.admin.data-grid.'.$column) ){
                        $column = config('doxa.admin.data-grid.'.$column)['default'];
                        $_columns[$column['index']] = $column;
                    }
                } else {
                    $_columns[$column['index']] = $column;
                }
            }
        }
        // dd($_columns);
        return $_columns;
    }

    public function getDatagridSorting()
    {
        $default_sorting = [
            'column' => 'id',
            'order' => 'desc'
        ];

        return !empty($this->config['datagrid']['sorting']) ? $this->config['datagrid']['sorting'] : $default_sorting;
    }

    public function isSortableByPosition(): bool
    {
        if(!empty($this->config->get('datagrid'))){
            if(!empty($this->config->get('datagrid')['sortable_by_position'])){
                return $this->config->get('datagrid')['sortable_by_position'];
            }
        }
        return false;
    }

    public function isPaginationDisabled(): bool
    {
        if(!empty($this->config->get('datagrid'))){
            if(!empty($this->config->get('datagrid')['disable_pagination'])){
                return $this->config->get('datagrid')['disable_pagination'];
            }
        }
        return false;
    }

    public function getDefaultPaginationPerPage()
    {
        return !empty($this->config['datagrid']['pagination']['per_page']) ? (int)$this->config['datagrid']['pagination']['per_page'] : $this->per_page_default;
    }

    public function getDatagridFieldType($key)
    {
        foreach($this->config->get('datagrid')['columns'] as $key => &$column){
            return $column['type'];
        }
    }
}
