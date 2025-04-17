<?php

namespace Doxa\Core\Libraries\Package;

use Illuminate\Support\Collection;

class Block
{
    public string $title = '';

    public bool $chanel_ws_locale_selector;

    public Collection $fields;

    public bool $is_variations;

    public bool $dates_and_editor = false;

    public bool $variations_dates_vs_editor = false;
    

    public function __construct($block)
    {
        $this->add(collect($block));
    }

    private function add($block): void
    {
        if($block->has('title')){
            $title = $block->get('title');
            $this->title = is_array($title) ? omniModuleTrans(...$title) : trim($title);
        }
        
        $this->chanel_ws_locale_selector = $block->get('chanel_ws_locale_selector') ?? false;

        if($block->has('fields')){
            $this->fields = collect($block->get('fields'))->mapInto(Field::class);
        }

        $this->is_variations = $block->get('is_variations') ?? false;

        if($block->has('dates_and_editor')){
            $this->dates_and_editor = (bool)$block->get('dates_and_editor');
        }

        if($block->has('variations_dates_vs_editor')){
            $this->variations_dates_vs_editor = (bool)$block->get('variations_dates_vs_editor');
        }
        

    }

}
