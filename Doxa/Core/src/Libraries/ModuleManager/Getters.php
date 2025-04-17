<?php

namespace Doxa\Core\Libraries\ModuleManager;

use Doxa\Core\Helpers\Logging\Clog;
use Illuminate\Support\Facades\DB;

trait Getters
{
    protected string $operation_table = '';

    protected string $operation_id;

    //protected string $operation_mode = '';

    protected Object|null $query_builder = null;

    /************************************************************************/
    /************************ Public getter methods *************************/
    /************************************************************************/

    // public function find($id, $variation = false)
    // {
    //     if($this->query_builder){
    //         return $this->query_builder->find($id);
    //     }

    //     if($this->operation_table == 'variation' || $variation){
    //         return $this->findVariation($id);
    //     } else {
    //         return $this->where('id', $id)->first();
    //     }
    // }

    public function first()
    {
        if($this->query_builder){
            return $this->query_builder->first();
        }

        $this->getBaseRecords();
        if(!$this->base_records){
            return false;
        }

        $this->base_records = [reset($this->base_records)];

        $this->getIds('base');

        $this->tryGetVariations();

        $this->getRelation();

        if (!isset($this->base_records[0])) {
            return false;
        }

        $this->dump('>>> RESULT >>> : ', $this->base_records[0]);

        $this->current_record = $this->base_records[0];
        
        return $this->current_record;

    }

    public function get()
    {
        if($this->query_builder){
            return $this->query_builder->get();
        }
        //dump('$customMethod: ', $customMethod);

        //$this->request_type = 'list';

        $this->getBaseRecords();
        if(!$this->base_records){
            return false;
        }

        self::$test_mode && dump($this->base_records);

        //$this->getIds('base');
        

        //Clog::write('t300', '004 this->base_records: ' . json_encode($this->base_records));

        $this->tryGetVariations();

        //Clog::write('t300', '005 this->base_records: ' . json_encode($this->base_records));

        $this->getRelation();
        
        if(!empty($this->base_records)){
            $this->trySort();
        }
        

        //Clog::write('t300', '006 this->base_records: ' . json_encode($this->base_records));

        //if($this->module == 'category'){
            //Clog::write('t300', 'this->base_records: '.json_encode($this->base_records));
            //dump('>>> RESULT >>> : ', $this->base_records);
        //}


        // if($this->method){
        //     $customMethod = $this->method;
        // }

        // if($customMethod){
        //     $this->tryCustomizeResult($customMethod);
        // }

        return $this->base_records;

    }

    public function trySort()
    {
        if(!empty($this->order)){
            $column = $this->order[0]['column'];
            $this->direction = $this->order[0]['direction'];
            $a = explode('.', $column);
            
            $this->sort_column = isset($a[1]) ? $a[1] : $column;

            if(!empty($this->base_records[0]->{$this->sort_column})){
                usort($this->base_records, array($this,'sortByOrder'));
            }

        }
    }

    public function getList()
    {
        $this->request_type = 'list';

        $this->getBaseRecords();
        if(!$this->base_records){
            return false;
        }

        $this->getIds('base');

        //Clog::write('t300', '004 this->base_records: ' . json_encode($this->base_records));

        $this->tryGetVariations();

        //Clog::write('t300', '005 this->base_records: ' . json_encode($this->base_records));

        $this->getRelation();

        $this->dump('>>> RESULT >>> : ', $this->base_records);
        //Clog::write('t300', 'this->base_records: ' . json_encode($this->base_records));

        $this->tryCustomizeResult(__FUNCTION__);

        //dd('>>>>>>>>>>>>> $this->base_records: ',$this->base_records);

        return $this->base_records;

    }

    public function isExists()
    {
        $this->request_type = 'single';

        $this->getBaseRecords();
        if(!$this->base_records){
            return false;
        }

        return reset($this->base_records);
    }

    protected function sortByOrder($a, $b) {
        $d = $a->{$this->sort_column} > $b->{$this->sort_column};
        if(strtolower($this->direction) == 'asc'){
            return $d > 0 ? 1 : -1;
        } else {
            return $d > 0 ? -1 : 1;
        }
    }

    /**** END ***************************************************************/

    

}
