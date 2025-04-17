<?php

namespace Doxa\Core\Libraries\ModuleManager;

use Doxa\Core\Helpers\Chlo;
use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\DB;

trait History
{

    public function writeHistory($action)
    {
        $pm = new Manager($this->module);
        $pm
            ->with(['images', 'related' => ['method' => 'relatedList']])
            ->where('id', $this->id)
            ->config('nested_variations', 'assoc_variations');
        $pm->item = $pm->first();

        //dump($pm->item);

        $data = $pm->getActualData($pm);

        //dump($data);

        DB::table('history')->insert([
            'action' => $action,
            'data' => json_encode($data),
            'created_at' => now(),
            'src_id' => $this->id,
            'src_type' => $this->module,
            'admin_id' => auth()->guard('admin')->user()->id
        ]);
    }

    public function getActualData($pm)
    {
        $data = [];
        foreach($pm->package->getEditingFields() as $key => $field){
            $data[$key] = $pm->item->{$key};
        }
        if($pm->package->hasVariations()){
            foreach(Chlo::asAssocById() as $channel_id => $channel){
                foreach($channel->locales as $locale_id => $locale){
                    foreach($pm->package->getEditingVariationFields() as $key => $field){
                        $data['variation'][$channel_id][$locale_id][$key] = 
                        !$pm->item ? '' : (isset($pm->item->assoc_variations[$channel_id][$locale_id]) ? $pm->item->assoc_variations[$channel_id][$locale_id]->$key : '');
                    }
                }
            }
        }
        return $data;
    }


    public function getHistory($id)
    {
        
        $list =  DB::table('history')
            ->select('action', 'created_at', 'admin_id')
            ->where('src_id', $id)
            ->where('src_type', $this->module)
            ->orderBy('created_at', 'desc')
            ->get();

        if($list){
            foreach($list as $item){    
                $item->admin_name = Admin::find($item->admin_id)->name;
                $item->created_at = date('d.m.Y H:i', strtotime($item->created_at));
            }
        }

        return $list;
    }
    
        
}
