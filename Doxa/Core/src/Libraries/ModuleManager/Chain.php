<?php

namespace Doxa\Core\Libraries\ModuleManager;

use Doxa\Core\Libraries\Chlo;
use Doxa\Core\Helpers\Error;
use Illuminate\Support\Facades\DB;
use Doxa\Core\Libraries\QueryBuilder\Where;

trait Chain
{

    protected $with = ['variations' => true];

    protected $tables = [];

    /************************************************************************/
    /************************ Chain public methods **************************/
    /************************************************************************/

    public function find($id, $type = 'base')
    {
        // if (!$this->qbm) {
        //     return false;
        // }

        // if (!$this->query_builder) {
        //     $this->table($type);
        // }

        // $this->query_builder->where('id', $id);

        // return $this;

        if ($this->qbm) {
            if (!$this->query_builder) {
                $this->table($type);
            }
            return $this->query_builder->where('id', $id)->first();
        } else {
            if ($type == 'variation' || $type == 'v') {
                if ($this->package->hasVariations()) {
                    $_type = 'variation';
                    $this->operation_table = $this->variations_table;
                }
            } else {
                $_type = 'base';
                $this->operation_table = $this->table;
            }
            if (!$this->operation_table) {
                $this->operation_table = $this->table;
            }
            if (!$this->operation_table) {
                return false;
            }


            if ($_type == 'variation') {
                return $this->findVariation($id);
            } else {
                return $this->where('id', $id)->first();
            }
        }
    }

    public function config()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            //dump('$arg: ', $arg);
            if (is_array($arg)) {
                foreach ($arg as $key => $value) {
                    if (is_int($key)) {
                        $this->setRequestConfig($value, true);
                    } else {
                        $this->setRequestConfig($key, $value);
                    }
                }
            } else {
                if (is_string($arg)) {
                    $this->setRequestConfig($arg, true);
                }
            }
        }

        return $this;
    }

    /**
     * METHOD FOR qbm mode only
     *
     * @param [type] $table
     * @return void
     */
    public function table($table = null)
    {
        if (!$this->qbm) {
            return false;
        }

        $_table = $this->table;
        if ($table) {
            if ($table == 'variation') {
                $_table = $this->variations_table;
            } else {
                if ($table == 'base') {
                    $_table = $this->table;
                } else {
                    $_table = $table;
                }
            }
        } else {
            $_table = $this->table;
        }

        $this->query_builder = DB::table($_table);
        return $this;
    }

    public function where()
    {
        $args = func_get_args();
        if (empty($args)) {
            return $this;
        }
        if ($this->qbm) {
            if (!$this->query_builder) {
                $this->table();
            }
            $this->query_builder->where(...$args);
            return $this;
        }

        if (sizeof($args) == 1) {
            // if 1 argiment - it's must be an array
            if (is_array($args[0])) {
                foreach ($args[0] as $key => $val) {

                    if (is_int($key)) {
                        // it's a sequential member, the value mustr be array
                        // examples:
                        // 0 => ['id', 8]
                        // 2 => ['date', '>', '12-12-2012']
                        if (!is_array($val)) {
                            Error::add('Wrong where: sequential member must be array ' . gettype($val) . ' given');
                            continue;
                        }
                        $this->wheres[] = new Where($val, $this->config);
                    } else {
                        // it's a key => value pair
                        // examples:
                        // 'id' => 8
                        // 'id >' => 2
                        $this->wheres[] = new Where([$key, $val], $this->config);
                    }
                }
            } else {
                Error::add('Wrong where: single argument must be array ' . gettype($args[0]) . ' given');
            }
        } else {
            if (sizeof($args) == 2) {
                $this->wheres[] = new Where([$args[0], $args[1]], $this->config);
            } else {
                $this->wheres[] = new Where([$args[0], $args[1], $args[2]], $this->config);
            }
        }

        return $this;
    }

    public function whereRaw($raw)
    {
        if (!$this->qbm) {
            return false;
        }

        if (!$this->query_builder) {
            $this->table('base');
        }

        $this->query_builder->whereRaw($raw);

        return $this;
    }


    public function whereIn()
    {
        $args = func_get_args();

        if (!$this->qbm) {
            $this->wheres[] = new Where([$args[0], 'IN', $args[1]], $this->config);
        } else {
            if (!$this->query_builder) {
                $this->table('base');
            }
            $this->query_builder->whereIn(...$args);
        }

        return $this;
    }

    public function with()
    {
        $this->query_builder = null;

        $args = func_get_args();
        // dump('>>>>>>>>> with()',$args, '-------------');
        foreach ($args as $arg) {
            //dump('LOOP, $arg: ', $arg);
            if (is_array($arg)) {
                // dump('is array');
                foreach ($arg as $key => $value) {
                    if (is_int($key)) {
                        // dump('is_int('.$key.')');
                        $this->with[$value] = true;
                    } else {
                        // dump('!is_int('.$key.')');
                        // dump('$value: ', $value);
                        $this->with[$key] = $value;
                    }
                }
                //dd(11);
            } else {
                if (is_string($arg)) {
                    //$this->with[] = $arg;
                    $this->with[$arg] = true;
                } else {
                    // dump('Wrong with: value must be string, ' . gettype($arg).' given');
                    Error::add('Wrong with: value must be string, ' . gettype($arg) . ' given');
                }
            }
        }
        //dump($this->with);

        return $this;
    }

    public function select()
    {

        if ($this->qbm && $this->query_builder) {
            $this->query_builder->select(...func_get_args());
            return $this;
        }
        $args = func_get_args();
        foreach ($args as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $value) {
                    if (!is_string($value)) {
                        Error::add('Wrong select: value must be string, ' . gettype($value) . ' given');
                        return false;
                    }
                    $this->select[] = $value;
                }
            } else {
                if (is_string($arg)) {
                    $this->select[] = $arg;
                } else {
                    Error::add('Wrong select: value must be string, ' . gettype($arg) . ' given');
                }
            }
        }

        return $this;
    }

    public function paginate($n)
    {
        if ($this->qbm && $this->query_builder) {
            return $this->query_builder->paginate($n);
        }
        return $this->limit($n);
    }

    public function channel($val)
    {
        if ($this->package->isChannelsIgnored()) {
            $this->channel_id = 0;
            return $this;
        }

        if ($val == 'current') {
            $this->channel_id = Chlo::getCurrentChannelId();
            return $this;
        }

        if ($val == 'default') {
            $this->channel_id = Chlo::getDefaultChannelId();
        }

        $this->channel_id = Chlo::isChannelExists($val) ? (int)$val : null;
        return $this;
    }

    public function locale($val)
    {
        if ($val == 'current') {
            $this->locale_id = Chlo::getCurrentLocaleId();
            return $this;
        }

        Chlo::set(locale: $val);

        $this->locale_id = Chlo::getCurrentLocaleId();
        if (!$this->locale_id) {
            die('Locale not found: ' . $val);
        }

        return $this;
    }

    public function method($val)
    {
        $this->method = $val;
        return $this;
    }

    public function package($package)
    {
        $this->package = $package;
        return $this;
    }


    public function base()
    {
        $this->operation_table = $this->table;
        return $this;
    }

    public function variation()
    {
        $this->operation_table = $this->variations_table;
        return $this;
    }

    public function orderBy($by, $direction = 'asc')
    {
        $this->order[] = [
            'column' => $by,
            'direction' => $direction
        ];

        return $this;
    }

    public function limit($limit, $page = 1)
    {
        $this->limit = $limit;

        return $this;
    }

    public function addTable($table)
    {
        if (is_array($table)) {
            $this->tables[] = array_merge($this->tables, $table);
        } else {
            $this->tables[] = $table;
        }
    }

    /**** END ***************************************************************/
}
