<?php

namespace Doxa\Core\Libraries\Package;

use Illuminate\Support\Collection;

class Column
{
    public string $classes = '';

    public Collection $blocks;

    public function __construct($column)
    {
        $this->add(collect($column));
    }

    private function add(Collection $column): void
    {
        if($column->has('classes')){
            $this->addClasses($column->get('classes'));
        }
        if($column->has('blocks')){
            $this->blocks = collect($column->get('blocks'))->mapInto(Block::class);
        }
    }
    
    private function addClasses(string $classes): void
    {
        $this->classes = $classes;
    }

}
