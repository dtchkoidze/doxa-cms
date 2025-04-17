<?php

namespace Doxa\Admin\View\Components;

use Closure;
use Illuminate\View\Component;
use Doxa\Core\Helpers\Logging\Clog;
use Illuminate\Contracts\View\View;

class FormControl extends Component
{

    private $channel;

    private $locale;

    private $key;

    //private $checked;

    public function __construct(
        protected $item, 
        protected $field, 
        $channel = null, 
        $locale = null,
    )
    {
        //dump($this->field);
        $this->channel = $channel;
        $this->locale = $locale;
        $this->key = $this->field->key;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $re = [
            'item' => $this->item,
            'key' => $this->key,
            'channel' => $this->channel,
            'locale' => $this->locale,

            'name' => $this->getName(),
            'field_dotted_name' => $this->getDottedName(),
            'id' => $this->getId(),
        ];

        switch ($this->field->control){
            case 'checkbox':
                $re['checked'] = $this->getChecked();
                break;
            case 'img':
                $re['images'] = $this->getImages();
                break;   
            default:
                $re['value'] = $this->getValue();
                break;    
        }

        //dump($this->item);
        

        switch ($this->field->type){
            case 'related':
                $this->getRelatedOptions();
                break;
            default:
                break; 
        }

        // $this->getRequiredLabel();
        // $this->getFieldRule();

        $re['field'] = $this->field;

        return view('doxa-admin::components.form.fields.'.$this->field->control, $re);
    }

    private function getRequiredLabel()
    {
        // if(!empty($this->field->required)){
        //     //dump($this->field->required);
        //     // if(!is_array($this->field->required)){
        //     //     dd('!array');
        //     // }
        //     $a = (array)$this->field->required;
        //     $this->field->required = omniModuleTrans(...(array)$this->field->required);
        // }
    }

    private function getFieldRule()
    {
        // if(!empty($this->field->rule)){
        //     $a = $this->field->required;
        //     $this->field->rule = omniModuleTrans(...$a);
        // }
    }

    private function getName()
    {
        return $this->channel ? 'variation['.$this->channel->id.']['.$this->locale->id.']['.$this->key.']' :  $this->key;
    }

    private function getDottedName()
    {
        return $this->channel ? 'variation.'.$this->channel->id.'.'.$this->locale->id.'.'.$this->key : $this->key;
    }

    private function getId()
    {
        return $this->channel ? 'variation_'.$this->channel->id.'_'.$this->locale->id.'_'.$this->key : $this->key;
    }

    private function getChecked()
    {
        //dump($this->field);
        if($this->channel){
            //Clog::write('FormControl', '$this->channel: '.$this->channel->id);
            $checked = isset(old('variation')[$this->channel->id][$this->locale->id]) ? 
                (!empty(old('variation')[$this->channel->id][$this->locale->id][$this->key]) ? true : false)
                :
                (!empty($this->item->assoc_variations[$this->channel->id][$this->locale->id]->{$this->key}) ? true : false)
            ;    
        } else {
            //Clog::write('FormControl', 'NO channel');
            //Clog::write('FormControl', 'old($this->key) ?: '.old($this->key));
            $checked = old($this->key) ?? (!empty($this->item->{$this->key}) ? true : false); 
        }
        //Clog::write('FormControl', $this->key.', $checked: '.$checked);
        return $checked;
    }

    private function getValue()
    {
        if($this->channel){
            $value = old('variation')[$this->channel->id][$this->locale->id][$this->key] ?? 
                (isset($this->item->assoc_variations[$this->channel->id][$this->locale->id]->{$this->key}) ? 
                    $this->item->assoc_variations[$this->channel->id][$this->locale->id]->{$this->key} 
                    : 
                    ''
                );
        } else {
            $value = old($this->key) ?? (isset($this->item->{$this->key}) ? $this->item->{$this->key} : false); 
        }
        return $value;
    }

    private function getImages()
    {
        if(empty($this->field->multiple)){
            $this->field->multiple = '';
        }
        if(!$this->item){
            return [];
        }
        //dump($this->key);
        //$images = $this->item->{$this->key};
        $images = $this->item->imagesArrayByKey($this->key);
        //dd($images);
        
        return $images ?? [];
    } 
    
    private function getRelatedOptions()
    {
        $list_method = $this->field->key.'RelatedList';
        $this->field->modal_ref = 'relatedModalWinFor_'.$this->field->module;
        $this->field->template_id = 'v-related-'.$this->field->module.'-handler';
        if($this->item){
            if(method_exists($this->item, $list_method)){
                $this->field->related_records = $this->item->$list_method();
            } else {
                $this->field->related_records = $this->item->{$this->field->key};
            }
        } else {
            $this->field->related_records = [];
        }
        
    }


}
