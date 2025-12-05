<?php

namespace Doxa\Core\Libraries\Package;

trait Editing
{
    protected $editingFields = [];

    protected $editingFieldsByType = [];

    public function getEditingHandler()
    {
        return !empty($this->config['editing']['handler']) ? $this->config['editing']['handler'] : 'laravel';
    }

    private function getEditingFieldsInfo()
    {
        // dd($this->columns);
        foreach($this->columns as $column){
            foreach($column->blocks as $block){
                if(!isset($block->fields)){
                    dd($this->module, $block);
                }
                foreach($block->fields as $field){
                    $this->editingFields[$field->key] = $field;
                    if($field->is_variation){
                        $this->editingFieldsByType['variation'][$field->key] = $field;
                    } else {
                        $this->editingFieldsByType['base'][$field->key] = $field;
                    }
                }
            }
        }
    }

    public function getAllEditingFields(): array
    {
        return $this->editingFields;
    }

    public function getEditingFields(): array
    {
        empty($this->editingFields) && $this->getEditingFieldsInfo();
        return $this->editingFieldsByType['base'] ?? [];
    }

    public function getEditingVariationFields(): array
    {
        empty($this->editingFields) && $this->getEditingFieldsInfo();
        if(isset($this->editingFieldsByType['variation'])){
            return $this->editingFieldsByType['variation'];
        }  
        return []; 
    }


    public function isEditingFieldExists($key, $type = 'base')
    {
        empty($this->editingFields) && $this->getEditingFieldsInfo();
        return !empty($this->editingFields[$type][$key]);
    }
}
