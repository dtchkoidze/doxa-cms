<?php

namespace Doxa\Core\Libraries\Package;

use Illuminate\Support\Collection;

class Field
{
    public bool $is_variation = false;

    public string $type;

    public string $control;

    public string $title = '';

    public string $key;

    public ?string $required = '';

    public ?string $rule = '';

    public Collection $validation_rules;

    public function __construct($field)
    {
        $this->add(collect($field));
    }

    private function add(Collection $field): void
    {
        $field->each(function ($value, $key) {
            switch ($key) {
                case 'title':
                    $this->setTitle($value);
                    break;
                case 'validation_rules':
                    $this->setValidationRules($value);
                    break;
                case 'required':
                    $this->setRequired($value);
                    break;  
                case 'rule':
                    $this->setRule($value);
                    break;  
                case 'variation':
                    $value ? $this->is_variation = (bool)$value : $this->is_variation = false;
                    break;    
                default:
                    $this->$key = $value;
                    break;
            }
        });
    }

    private function setTitle($title): Void
    {
        if($title){
            $this->title = is_array($title) ? omniModuleTrans(...$title) : trim($title);
        } 
    }

    private function setRule($data): Void
    {
        if($data){
            $this->rule = omniModuleTrans(...$data);
        }
    }

    private function setRequired($data): Void
    {
        if($data){
            $this->required = omniModuleTrans(...$data);
        } 
    }

    private function setValidationRules($validation_rules): Void
    {
        $this->validation_rules = collect($validation_rules);
    }

}
