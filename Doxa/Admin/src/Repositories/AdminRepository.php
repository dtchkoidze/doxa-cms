<?php

namespace Doxa\Admin\Repositories;

use Doxa\Core\Libraries\Chlo;
use Illuminate\Support\Facades\DB;
use Doxa\Core\Repositories\Repository;
use Doxa\Core\Libraries\Package\Package;

class AdminRepository extends Repository
{

    // protected $item;

    // protected $id;

    // protected $data;

    // public function __construct(protected Package $package) {}

    // public function checkItemExists($id)
    // {
    //     return DB::table($this->package->scheme->getTable())->where('id', $id)->first();
    // }

    // public function getEditedItem(int $id)
    // {
    //     return $this->item = $this->mm->where('id', $id)->getEditingItem();
    // }

    // public function getActualData()
    // {
    //     $data = [];
    //     foreach ($this->package->getEditingFields() as $key => $field) {
    //         $data[$key] = !$this->item ? '' : $this->item->{$key};
    //     }
    //     if ($this->package->hasVariations()) {
    //         foreach (Chlo::altAsAssocById() as $channel_id => $channel) {
    //             foreach ($channel->locales as $locale_id => $locale) {
    //                 foreach ($this->package->getEditingVariationFields() as $key => $field) {
    //                     $data['variation'][$channel_id][$locale_id][$key] =
    //                         !$this->item ? '' : (isset($this->item->assoc_variations[$channel_id][$locale_id]) ? $this->item->assoc_variations[$channel_id][$locale_id]->$key : '');
    //                 }
    //             }
    //         }
    //     }
    //     return $data;
    // }
}
