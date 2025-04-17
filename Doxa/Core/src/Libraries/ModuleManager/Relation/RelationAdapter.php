<?php

namespace Doxa\Core\Libraries\ModuleManager\Relation;

use Doxa\Core\Libraries\Package\Package;
use Doxa\Core\Libraries\Package\Relation;
use Doxa\Core\Libraries\ModuleManager\Relation\modes\WithConnect;
use Doxa\Core\Libraries\ModuleManager\Relation\modes\WithSrcTable;
use Doxa\Core\Libraries\ModuleManager\Relation\modes\WithoutConnect;

class RelationAdapter
{

    /**
     * Return a handler for a given relation
     * 
     * @param Relation $relation
     * @param Package $package
     * @return WithConnect|WithoutConnect|WithSrcTable
     */
    
    static public function getHandler($relation, $package)
    {
        switch($relation->mode()){
            case Relation::MODE_WITH_CONNECT_TABLE:
                return new WithConnect($relation, $package);
            case Relation::MODE_WITH_CUSTOM_CONNECT_TABLE:
                return new WithConnect($relation, $package);
            case Relation::MODE_WITHOUT_CONNECT_TABLE:
                return new WithoutConnect($relation, $package);
            case Relation::MODE_WITH_SRC_TABLE:
                return new WithSrcTable($relation, $package);   
            default:
                return new WithConnect($relation, $package);
        }
    }
}
