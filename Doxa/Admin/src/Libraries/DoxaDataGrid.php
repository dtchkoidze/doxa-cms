<?php

namespace Doxa\Admin\Libraries;

use Illuminate\Support\Str;
use Doxa\Core\Libraries\Chlo;
use Illuminate\Support\Facades\DB;
use Doxa\Core\Libraries\Logging\Clog;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Crypt;
use Doxa\Admin\Exports\DataGridExport;
use Doxa\Core\Libraries\Datagrid\Action;
use Doxa\Core\Libraries\Datagrid\Column;
use Doxa\Core\Libraries\Package\Relation;
use Illuminate\Database\Query\JoinClause;
use Doxa\Core\Libraries\Datagrid\MassAction;
use Illuminate\Pagination\LengthAwarePaginator;
use Doxa\Core\Libraries\Datagrid\Enums\ColumnTypeEnum;
use Doxa\Core\Libraries\ModuleManager\Manager as ModuleManager;
use Doxa\Core\Libraries\ModuleManager\Relation\RelationAdapter;


class DoxaDataGrid
{

    protected $module;
    protected $package;
    protected $model;
    protected $scheme;

    protected int $current_channel_id = 0;
    protected int $current_locale_id = 0;

    protected $table;
    protected $variations_table;

    protected $package_columns;
    protected $columns;
    protected $actions;
    protected array $massActions = [];

    protected $queryBuilder;

    protected $requestedParams;

    protected string $primaryColumn = 'id';
    protected array $sorting = [
        'column' => null,
        'order' => null,
    ];
    //protected ?string $sortColumn;
    //protected string $sortOrder = 'desc';

    //protected $itemsPerPage = 10;
    
    protected LengthAwarePaginator $paginator;

    protected bool $exportable = false;
    protected mixed $exportFile = null;


    protected $data;
    
    protected $related_lists = [];

    public function __construct()
    {
    }

    public function configure(array $data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        // if(request('current_channel_id') && request('current_locale_id')){
        //     dump(request('current_channel_id'), request('current_locale_id'));
        //     Chlo::set(channel: request('current_channel_id'), locale: request('current_locale_id'));
        // }

        //dd(Chlo::$instance);

        if(request('current_channel_id')){
            //dump("request('current_channel_id'): ".request('current_channel_id'));
            Chlo::set(channel: request('current_channel_id'));
        }

        if(request('current_locale_id')){
            //dump("request('current_locale_id'): ".request('current_locale_id'));
            Chlo::set(locale: request('current_locale_id'));
        }

        if(!$this->package->isChannelsIgnored()){
            $this->current_channel_id = Chlo::getCurrentChannelId();
            if(!$this->current_channel_id){
                Chlo::setDefaultChannelAsCurrent();
                $this->current_channel_id = Chlo::getCurrentChannelId();
            }
        }

        if($this->package->hasVariations()){
            $this->current_locale_id = Chlo::getCurrentLocaleId();
            if(!$this->current_locale_id){
                Chlo::setDefaultLocaleAsCurrent();
                $this->current_locale_id = Chlo::getCurrentLocaleId();
            }
        }

        // dump([
        //     'current_channel_id' => $this->current_channel_id,
        //     'current_locale_id' => $this->current_locale_id
        // ]);


        if(!empty($this->model)){
            $this->table = $this->model->getTable();
            if ($this->package->hasVariations()) {
                $this->variations_table = $this->module . '_variations';
            }
        } else {
            $this->table = $this->package->scheme->getTable();
            if ($this->package->hasVariations()) {
                $this->variations_table = $this->package->scheme->getVariationsTable();
            }
        }

        // sorting
        $this->sorting = $this->package->getDatagridSorting();
        if($this->sorting['column'] == 'id'){
            $this->sorting['column'] = $this->table.'.id';
        }

        return $this;

        // $this->preparePackageColumns();
    }

    public function getData()
    {
        $this->process();
        $this->data = $this->formatData();
        $this->resolveCommonData();
        
        return $this->data;
    }

    public function process(): void
    {
        // preparing columns, actions, mass actions
        $this->preparePackageColumns();
        $this->prepareColumns();
        $this->prepareActions();
        $this->prepareMassActions();

        // start query builder
        $this->prepareQueryBuilder();

        // process request
        $this->validatedRequest();
        $this->processRequestedFilters();
        $this->processRequestedSorting();
        $this->processRequestedExport();
        $this->processRequestedPagination();
    }

    /**
     * This method prepares the package columns:
     * - adds 'key' column, which may be different from index, depending on variations, if variatios exists key will contain table name and column name:
     *      service.position, portfolio_variations.title and so on
     * - adds 'builder_select' column, which will be used in query builder. it's contains array of select directives for query builder
     * - resolves "override" and "preffered" config data 
     *
     * @return void
     */
    protected function preparePackageColumns()
    {
        $this->package_columns = $this->package->getDatagridColumns();

        foreach ($this->package_columns as &$column) {

            // --- images
            if ($column['type'] == 'image') {
                $column['key'] = $column['index'];

                $tb = 'base';
                if(isset($column['variation'])){
                    if($column['variation']){
                        $tb = 'variation';
                    }
                }
                $relation = $this->package->scheme->getRelationByKey($column['index'], [], $tb);
                if($relation->mode() != Relation::MODE_WITHOUT_CONNECT_TABLE){
                    continue;
                }
                
            }

            // --- no variations - use index as key
            if (!$this->package->hasVariations()) {
                $column['key'] = $column['index'];
                continue;
            }

            $column['builder_select'] = [];
            $base = $variation = false;
            if (empty($column['variation']) && empty($column['base'])) {
                $base = true;
            } else {
                if (!empty($column['variation'])) {
                    $variation = true;
                }
                if (!empty($column['base'])) {
                    $base = true;
                }
            }
            if ($variation) {
                $column['builder_select'][] = $this->variations_table . '.' . $column['index'] . ' as ' . $this->variations_table . '.' . $column['index'];
            }
            if ($base) {
                $column['builder_select'][] = $this->table . '.' . $column['index'] . ' as ' . $this->table . '.' . $column['index'];
            }

            if ($variation) {
                $column['key'] = $this->variations_table . '.' . $column['index'];
            } else {
                $column['key'] = $this->table . '.' . $column['index'];
            }

            /**
             * Rules if field have both base and variation flag
             */
            if ($variation && $base) {
                if (!empty($column['vb'])) {
                    /**
                     * Overrided means that we will override vb['base'] and vb['variation'] and value of overrided will be used if not empty
                     * if empty - base will be used
                     * example: url_key in services can be defined in base table and in variation table. By default we're using base, but
                     * user can customize url_key for concrete variation. If we set overrided = 'variation' then url_key will be taken from variation 
                     * table (if not empty).
                     */
                    if (!empty($column['vb']['overrided'])) {
                        if ($column['vb']['overrided'] == 'variation') {
                            $column['key'] = $this->variations_table . '.' . $column['index'];
                            $column['vb']['base'] = $this->table . '.' . $column['index'];
                            $column['vb']['overrided'] = $this->variations_table . '.' . $column['index'];
                            $column['vb']['active_field'] = $this->variations_table.'.active';
                            $column['vb']['variation'] = true;
                        } else {
                            $column['key'] = $this->table . '.' . $column['index'];
                            $column['vb']['overrided'] = $this->table . '.' . $column['index'];
                            $column['vb']['base'] = $this->variations_table . '.' . $column['index'];
                        }
                    }

                    /**
                     * Preffered means that we will only base or variation value use without replacement
                     */
                    if (!empty($column['vb']['preffered'])) {
                        if ($column['vb']['preffered'] == 'variation') {
                            $column['key'] = $this->variations_table . '.' . $column['index'];
                            $column['vb']['base'] = $this->table . '.' . $column['index'];
                        } else {
                            $column['key'] = $this->table . '.' . $column['index'];
                            $column['vb']['base'] = $this->variations_table . '.' . $column['index'];
                        }
                    }
                } else {
                    // no vb (variation-base) defined for this column, so variation is preffered by default .. look at previous block
                    // .... if($variation){ ....
                }
            }
        }
    }

    protected function prepareColumns()
    {
        foreach ($this->package_columns as $column) {
            if ($column['type'] == 'related') {
                $column['options'] = [
                    'type' => 'basic',
                    'params' => [
                        'options' => $this->getRelationList($column['index']),
                    ]
                ];
            }
            if ($column['type'] == 'datetime') {
                $column['type'] = 'date_range';
            }
            $this->addColumn($column);
        }
    }

    public function prepareActions()
    {
        if ($this->package->canEdit()) {
            $this->addAction([
                'icon' => 'fa-regular fa-pen-to-square',
                'title' => trans('admin::app.' . $this->module . '.datagrid.edit'),
                'method' => 'GET',
                'action' => 'edit',
                'url' => function ($row) {
                    return route('admin.' . $this->module . '.edit', [
                        'id' => $this->package->hasVariations() ? $row->src_id : $row->id
                    ]);
                },
            ]);
        }

        if ($this->package->canCreate()) {
            $this->addAction([
                'icon' => 'fa-regular fa-copy',
                'title' => trans('admin::app.' . $this->module . '.datagrid.copy'),
                'method' => 'GET',
                'action' => 'copy',
                'url' => function ($row) {
                    return route('admin.' . $this->module . '.copy', [
                        'id' => $this->package->hasVariations() ? $row->src_id : $row->id
                    ]);
                },
            ]);
        }

        if ($this->package->canDelete()) {

            $a = [
                'index' => 'delete',
                'icon' => 'fa-solid fa-trash text-red-500',
                'title' => omniModuleTrans($this->module, 'actions.delete'),
                'message' => omniModuleTrans($this->module, 'actions.delete_base_record_message'),
                'method' => 'delete',
                'url' => function ($row) {
                    return route('admin.' . $this->module . '.delete', [
                        'id' => $this->package->hasVariations() ? $row->src_id : $row->id
                    ]);
                },
            ];

            //$a = [];

            $a['confirmation'] = [
                'title' => omniModuleTrans($this->module, 'actions.delete'),
                'message' => omniModuleTrans($this->module, 'actions.delete_base_record_message'),
                'buttons' => [
                    [
                        'name' => 'delete' . $this->package->name,
                        'title' => omniModuleTrans($this->module, 'actions.delete'),
                        'button_class' => 'danger',
                        'method' => 'delete',
                        'url' => function ($row) {
                            return route('admin.' . $this->module . '.delete', [
                                'id' => $this->package->hasVariations() ? $row->src_id : $row->id
                            ]);
                        },
                    ],
                ]
            ];

            if ($this->package->hasVariations()) {

                $a['confirmation'] = [
                    'title' =>  omniModuleTrans($this->module, 'actions.delete_when_variations_qu'),
                    'message' => omniModuleTrans($this->module, 'actions.delete_when_variations_variants'),
                    'buttons' => [
                        [
                            'name' => 'delete' . $this->package->name,
                            'title' => omniModuleTrans($this->module, 'actions.delete_record'),
                            'button_class' => 'primary',
                            'method' => 'delete',
                            'url' => function ($row) {
                                return route('admin.' . $this->module . '.delete', [
                                    'id' => $row->src_id
                                ]);
                            }
                        ],
                        [
                            'name' => 'delete' . $this->package->name . 'Variation',
                            'title' => omniModuleTrans($this->module, 'actions.delete_variation'),
                            'button_class' => 'secondary',
                            'method' => 'delete',
                            'url' => function ($row) {
                                return route('admin.' . $this->module . '.variation.delete', [
                                    'id' => $row->id
                                ]);
                            }
                        ]
                    ]
                ];
            }

            $this->addAction($a);
        }
    }

    public function prepareMassActions()
    {
        if ($this->package->canDelete()) {
            if ($this->package->hasVariations()) {
                $this->addMassAction([
                    'title'  => omniModuleTrans($this->module, 'datagrid.toolbar.mass-actions.delete'),
                    'method' => 'Not used',
                    'action' => 'Not used',
                    'url'    => 'Not used',
                    'confirmation' => [
                        'title' =>  omniModuleTrans($this->module, 'mass_actions.delete_when_variations_qu'),
                        'message' => omniModuleTrans($this->module, 'mass_actions.delete_when_variations_variants'),
                        'buttons' => [
                            [
                                'name' => 'delete' . $this->package->name,
                                'title' => omniModuleTrans($this->module, 'mass_actions.delete_records'),
                                'button_class' => 'primary',
                                'action' => 'delete',
                                'method' => 'post',
                                'url' => route('admin.' . $this->module . '.mass_action')
                            ],
                            [
                                'name' => 'delete' . $this->package->name . 'Variation',
                                'title' => omniModuleTrans($this->module, 'mass_actions.delete_variations'),
                                'button_class' => 'secondary',
                                'action' => 'clearVariations',
                                'method' => 'post',
                                'url' => route('admin.' . $this->module . '.mass_action')
                            ]





                            // [
                            //     'title' => omniModuleTrans($this->module, 'actions.delete'),
                            //     'button_class' => 'primary',
                            //     'method' => 'POST',
                            //     'action' => 'delete',
                            //     'url' => route('admin.' . $this->module . '.mass_action')
                            // ]
                        ],
                    ],
                ]);
            } else {
                $this->addMassAction([
                    'title'  => omniModuleTrans($this->module, 'datagrid.toolbar.mass-actions.delete'),
                    'method' => 'POST',
                    'action' => 'delete',
                    'url'    => route('admin.' . $this->module . '.mass_action'),
                    'confirmation' => [
                        'title' =>  omniModuleTrans($this->module, 'datagrid.toolbar.mass-actions.confirmation.delete_qu'),
                        'message' => omniModuleTrans($this->module, 'datagrid.toolbar.mass-actions.confirmation.delete_message'),
                        'buttons' => [
                            [
                                'title' => omniModuleTrans($this->module, 'actions.delete'),
                                'button_class' => 'primary',
                                'method' => 'POST',
                                'action' => 'delete',
                                'url' => route('admin.' . $this->module . '.mass_action')
                            ]
                        ],
                    ],
                ]);
            }
        }
    }

    public function setQueryBuilder($queryBuilder = null): void
    {
        $this->queryBuilder = $queryBuilder ?: $this->prepareQueryBuilder();
    }

    public function prepareQueryBuilder()
    {
        if ($this->package->hasVariations()) {
            //dump('variations', '$this->current_locale_id: '. $this->current_locale_id);
            $this->queryBuilder = DB::table($this->variations_table)
                ->where('locale_id', $this->current_locale_id)
                ->join($this->table, $this->table . '.id', '=', $this->variations_table . '.src_id')
                ->select(
                    $this->variations_table . '.id',
                    $this->variations_table . '.locale_id',
                    $this->variations_table . '.channel_id',
                    $this->variations_table . '.src_id'
                    //$this->variations_table . '.active as '.$this->variations_table . '.active',
                );
            
            if(!$this->package->isChannelsignored()){
                $this->queryBuilder->where('channel_id', $this->current_channel_id);
            }    

            if ($this->package->isEditingFieldExists('active', 'variation')) {
                $this->queryBuilder->addSelect($this->variations_table . '.active as '.$this->variations_table . '.active',);
            }
            
            

            $this->addColumnSelects();
            //dd($this->queryBuilder->get());
        } else {
            $this->queryBuilder = DB::table($this->table);
        }
    }

    public function addColumnSelects()
    {
        foreach ($this->package_columns as $column) {
            if (!empty($column['builder_select'])) {
                foreach ($column['builder_select'] as $select) {
                    $this->queryBuilder->addSelect($select);
                }
            }
        }
        //dd($this->queryBuilder->get());
    }

    protected function addColumn(mixed $column): void
    {
        $this->columns[] = new Column(
            index: $column['key'],
            dbField: $column['dbField'] ?? null,
            label: is_array($column['label']) ? omniModuleTrans(...$column['label']) : trim($column['label']),
            type: $column['type'],
            control: $column['control'] ?? null,
            options: $column['options'] ?? null,
            searchable: $column['searchable'] ?? false,
            filterable: $column['filterable'] ?? false,
            sortable: $column['sortable'] ?? false,
            vb: $column['vb'] ?? null,
            closure: $column['closure'] ?? null,
            params: $column['params'] ?? null,
            variation: $column['variation'] ?? false,
        );
    }

    public function addAction(array $action): void
    {
        $this->actions[] = new Action(
            index: $action['index'] ?? '',
            icon: $action['icon'] ?? '',
            title: $action['title'] ?? '',
            message: $action['message'] ?? '',
            method: $action['method'] ?? '',
            action: $action['action'] ?? '',
            url: $action['url'] ?? '',
            confirmation: isset($action['confirmation']) ? $action['confirmation'] : null,
        );
    }

    public function addMassAction(array $massAction): void
    {
        $this->massActions[] = new MassAction(
            icon: $massAction['icon'] ?? '',
            title: $massAction['title'],
            method: $massAction['method'],
            action: $massAction['action'] ?? '',
            url: $massAction['url'],
            options: $massAction['options'] ?? [],
            confirmation: $massAction['confirmation'] ?? [],
        );
    }

    public function getRelationList($key)
    {
        if(!empty($this->related_lists[$key])){
            return $this->related_lists[$key];
        }

        $relation = $this->package->scheme->getRelationByKey($key);

        $pm = new ModuleManager(
            $relation->srcPackage()
        );

        $list = $pm
            ->where('active', 1)
            ->locale('current')
            ->channel('current')
            ->getRelationList();

        return $this->related_lists[$key] = $list;

    }

    public function validatedRequest(): Void
    {
        request()->validate([
            'filters' => ['sometimes', 'required', 'array'],
            'sort' => ['sometimes', 'required', 'array'],
            'pagination' => ['sometimes', 'required', 'array'],
            'export' => ['sometimes', 'required', 'boolean'],
            'format' => ['sometimes', 'required', 'in:csv,xls,xlsx'],
        ]);

        $this->requestedParams = request()->only(['filters', 'sort', 'pagination', 'export', 'format']);
    }

    public function processRequestedFilters()
    {
        $requestedFilters = $this->requestedParams['filters'] ?? [];
        //dump('$requestedFilters: ',$requestedFilters);

        foreach ($requestedFilters as $requestedColumn => $requestedValues) {
            if ($requestedColumn === 'all') {
                //dump('$requestedValues: ', $requestedValues);
                $this->queryBuilder->where(function ($scopeQueryBuilder) use ($requestedValues) {
                    foreach ($requestedValues as $value) {
                        //dump('$value: ', $value);
                        //dump('collect($this->columns): ', collect($this->columns));
                        //$c = collect($this->columns);
                        collect($this->columns)
                            ->filter(fn($column) => $column->searchable && $column->type !== ColumnTypeEnum::BOOLEAN->value)
                            //->each(fn ($column) => $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), 'LIKE', '%'.$value.'%'))
                            ->each(function ($column) use ($scopeQueryBuilder, $value) {
                                //dump($column, $scopeQueryBuilder, $value);
                                $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), 'LIKE', '%' . $value . '%');
                                if (!empty ($column->vb['base'])) {
                                    $table = explode('.', $column->vb['base'])[0];
                                    $scopeQueryBuilder->orWhere($column->vb['base'], 'LIKE', '%' . $value . '%');
                                }
                                return;
                            });
                    }
                });
            } else {
                $column = collect($this->columns)->first(fn($c) => $c->index === $requestedColumn);

                //Clog::write('test_dgrid', 'column: '.json_encode($column));

                if(!$column){
                    continue;
                }

                if($column->type == ColumnTypeEnum::STRING->value) {
                    //Clog::write('test_dgrid', 'STRING: '.$column->type.',  $column->index: '.$column->index);
                    $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), 'LIKE', '%'.$value.'%');
                        }
                    });
                    continue;
                }

                if($column->type == ColumnTypeEnum::INTEGER->value) {
                    //Clog::write('test_dgrid', 'INTEGER: '.$column->type.',  $column->index: '.$column->index);
                    $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), $value);
                        }
                        //dump('INTEGER',$scopeQueryBuilder);
                    });
                    continue;
                }

                if($column->type == ColumnTypeEnum::DROPDOWN->value) {
                    //Clog::write('test_dgrid', 'DROPDOWN: '.$column->type.',  $column->index: '.$column->index);
                    $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), $value);
                        }
                        //dump('DROPDOWN',$scopeQueryBuilder);
                    });
                    continue;
                }

                if($column->type == ColumnTypeEnum::DATE_RANGE->value) {
                    //Clog::write('test_dgrid', 'DATE_RANGE: '.$column->type.',  $column->index: '.$column->index);
                    $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                        foreach ($requestedValues as $value) {
                            if($value[0] && !$value[1]) {
                                $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), '>=', $value[0].' 00:00:01');
                            }
                            if(!$value[0] && $value[1]) {
                                $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), '<=', $value[1].' 23:59:59');
                            }

                            if($value[0] && $value[1]) {
                                $scopeQueryBuilder->whereBetween($column->getDatabaseColumnName(), [
                                    ($value[0] ?? '').' 00:00:01',
                                    ($value[1] ?? '').' 23:59:59',
                                ]);
                            }
                        }
                    });
                    continue;
                }

                if($column->type == ColumnTypeEnum::DATE_TIME_RANGE->value) {
                    //Clog::write('test_dgrid', 'DATE_TIME_RANGE: '.$column->type.',  $column->index: '.$column->index);
                    $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->whereBetween($column->getDatabaseColumnName(), [$value[0] ?? '', $value[1] ?? '']);
                        }
                    });
                    continue;
                }

                // related
                if($column->type == ColumnTypeEnum::RELATED->value) {
                    $relation = $this->package->scheme->getRelationByKey($column->index);
                    $relationHandler = RelationAdapter::getHandler($relation, $this->package);
                    $ids = $relationHandler->getConnectedIdsBySrc($requestedValues);
                    if(!empty($ids)) {
                        $this->queryBuilder->whereIn('news.id', $ids);
                    } else {
                        $this->queryBuilder->where('news.id', '=', 'foo');
                    }
                    
                    continue;
                }

                Clog::write('test_dgrid', 'NOT ENUM MATCHES');

            }
        }

        //return $this->queryBuilder;
    }

    public function processRequestedSorting()
    {
        $requestedSort = $this->requestedParams['sort'] ?? [];

        if(!empty($requestedSort['column'])){
            $this->sorting['column'] = $requestedSort['column'];
        }
        if(!empty($requestedSort['order'])){
            $this->sorting['order'] = $requestedSort['order'];
        }

        $this->queryBuilder->orderBy(
            $this->sorting['column'], 
            $this->sorting['order']
        );
    }

    public function processRequestedPagination(): Void
    {
       
        $requestedPagination = $this->requestedParams['pagination'] ?? [];

        $this->paginator = $this->queryBuilder->paginate(
            $requestedPagination['per_page'] ?? $this->package->getDefaultPaginationPerPage(),
            ['*'],
            'page',
            $requestedPagination['current_page'] ?? 1
        );
    }

    public function processRequestedExport()
    {
        /**
         * The `export` parameter is validated as a boolean in the `validatedRequest`. An `empty` function will not work,
         * as it will always be treated as true because of "0" and "1".
         */
        if (isset($this->requestedParams['export']) && (bool) $this->requestedParams['export']) {
            $this->exportable = true;
            $this->setExportFile($this->queryBuilder->get(), $this->requestedParams['format']);
        }
    }

    public function setExportFile($records, $format = 'csv')
    {
        //$this->exportFile = Excel::download(new DataGridExport($records), Str::random(36) . '.' . $format);
    }

    public function formatData(): array
    {
        /**
         * TODO: need to handle this...
         */
        foreach ($this->columns as $column) {
            $column->input_type = $column->getFormInputType();
            $column->options = $column->getFormOptions();
        }

        $paginator = $this->paginator->toArray();

        //dd($paginator);

        foreach ($paginator['data'] as $record) {
            $record = $this->sanitizeRow($record);
            $record = $this->applyParams($record);

            foreach ($this->columns as $column) {
                if ($closure = $column->closure) {
                    $record->{$column->index} = $closure($record);
                    $record->is_closure = true;
                }
                if($column->type == 'datetime' || $column->type == 'date_range') {
                    $record->{$column->index} = date('d.m.Y H:i', strtotime($record->{$column->index}));
                }
            }

            $record->actions = [];

            foreach ($this->actions as $index => $action) {

                $getUrl = $action->url;

                $_confirmation = null;
                if ($action->confirmation) {
                    $_confirmation = $action->confirmation;
                    foreach ($_confirmation['buttons'] as &$button) {
                        $_getUrl = $button['url'];
                        $button['url'] = $_getUrl($record);
                    }
                }

                $record->actions[] = [
                    'index' => !empty($action->index) ? $action->index : 'action_' . $index + 1,
                    'icon' => $action->icon,
                    'title' => $action->title,
                    'message' => $action->message,
                    'method' => $action->method,
                    'url' => $getUrl($record),
                    'id' => $this->package->hasVariations() ? $record->src_id : $record->id,
                    'action' => $action->action,
                    'confirmation' => $_confirmation,
                ];
            }
        }

        $records = $paginator['data'];

        if(!empty($records)){
            foreach ($this->columns as $column) {
                if($column->type == 'related' || $column->type == 'image') {
                    
                    $relation = $this->package->scheme->getRelationByKey($column->index, [], $column->variation ? 'variation' : 'base');
                    $relationHandler = RelationAdapter::getHandler($relation, $this->package);

                    if($relation->mode() == Relation::MODE_WITHOUT_CONNECT_TABLE){
                        //dump('Relation::MODE_WITHOUT_CONNECT_TABLE', $column);
                        $ids = [];
                        foreach($records as $record){
                            if($record->{$column->index}){
                                $ids = array_merge($ids, explode(',', $record->{$column->index}));
                                $ids = collect($ids)->unique()->toArray();
                                //dump('$ids', $ids);
                            }
                        }
                    } else {
                        if ($this->package->hasVariations() && !$column->variation) {
                            $ids = collect($records)->pluck('src_id')->toArray();
                        } else {
                            $ids = collect($records)->pluck('id')->toArray();
                        }
                        //dump($records);
                        //dump($ids);
                    }

                    if(empty($ids)){
                        $connected_index = collect([]);
                    } else {
                        $connected_index = $relationHandler->getConnectedRecordsIndex($ids);
                    }

                    foreach($records as $record){
                        if($relation->mode() == Relation::MODE_WITHOUT_CONNECT_TABLE){
                            
                            if($connected_index->isEmpty()){
                                $record->{$column->index} = [];
                                  continue;
                            }
                            if($record->{$column->index}){
                                $rels = explode(',', $record->{$column->index});
                                $record->{$column->index} = '';
                                if($column->control == 'related'){
                                    $a = [];
                                    foreach($rels as $rel_id){
                                        if(isset($connected_index[$rel_id])){
                                            if(!empty($connected_index[$rel_id]->title)){
                                                $a[] = $connected_index[$rel_id]->title;
                                            } else {
                                                if(!empty($connected_index[$rel_id]->name)){
                                                    $a[] = $connected_index[$rel_id]->name;
                                                } else {
                                                    if(!empty($connected_index[$rel_id]->email)){
                                                        $a[] = $connected_index[$rel_id]->email;
                                                    } else {
                                                        $a[] = $connected_index[$rel_id]->id;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $record->{$column->index} = implode(', ', $a);
                                }

                                if($column->control == 'image'){
                                    $exists = false;
                                    foreach($rels as $rel_id){
                                        if(isset($connected_index[$rel_id])){
                                            $record->{$column->index} = $this->getThumbForRecord([$connected_index[$rel_id]], sizeof($rels));
                                            $exists = true;
                                            break;
                                        }
                                    }    
                                    if(!$exists){
                                        $record->{$column->index} = [];
                                    }
                                   
                                }

                            } else {
                                $record->{$column->index} = [];
                            }

                        } else {

                            //dump('!!!Relation::MODE_WITHOUT_CONNECT_TABLE $record->id: '.$record->id);
                            if ($this->package->hasVariations() && !$column->variation) {
                                $identifiactor = 'src_id';
                            } else {
                                $identifiactor = 'id';
                            }

                            if(isset($connected_index[$record->$identifiactor])){
                                //dump('isset connected_index '.$record->$identifiactor);
                                $relateds = $connected_index[$record->$identifiactor];
                                if($column->control == 'related'){
                                    $relateds  = collect($relateds)->pluck('title')->toArray();
                                    $record->{$column->index} = implode(', ', $relateds);
                                }
                                if($column->control == 'image'){
                                    $record->{$column->index} = $this->getThumbForRecord($relateds);
                                }
                            } else {
                                $record->{$column->index} = [];
                            }
                        }
                    }
                }

            }
        }

        return [
            'id' => Crypt::encryptString(get_called_class()),
            'columns' => $this->columns,
            'actions' => $this->actions,
            'mass_actions' => $this->massActions,
            'records' => $records,
            
            'meta' => [
                //'primary_column' => $this->primaryColumn,
                'primary_column' => $this->package->hasVariations() ? 'src_id' : 'id',
                'paginator' => [
                    'from' => $paginator['from'],
                    'to' => $paginator['to'],
                    'total' => $paginator['total'],
                    'per_page_options' => [10, 20, 30, 50, 100],
                    'per_page' => $paginator['per_page'],
                    'current_page' => $paginator['current_page'],
                    'last_page' => $paginator['last_page'],
                ],
                'sort' => $this->sorting,

                'sortable_by_position' => $this->package->isSortableByPosition(),
                'disable_pagination' => $this->package->isPaginationDisabled(),
                'has_variations' => $this->package->hasVariations(),

                'current_channel_id' => $this->current_channel_id,
                'current_locale_id' => $this->current_locale_id,
            ],
        ];
    }

    protected function getThumbForRecord($relateds, $size = false)
    {
        if(is_array($relateds) && !$size){
            $size = sizeof($relateds);
        }

        $url = !@empty($relateds[0]->thumb_url) ? $relateds[0]->thumb_url : $relateds[0]->url;
        return 
            '<div class="relative">
                <div class="absolute z-10 w-4 h-4 bg-blue-600 rounded-full -left-1 -top-1 ">
                    <div class="flex justify-center text-xs text-white align-items-center">' . $size . '</div>
                </div>
                <img style="height: 30px;" src="' . $url . '"/>
            </div>';
    }

    // protected function getThumbForRecordWC($related, $size)
    // {
    //     //dump($relateds);
    //     return 
    //         '<div class="relative">
    //             <div class="absolute z-10 w-4 h-4 bg-blue-600 rounded-full -left-1 -top-1 ">
    //                 <div class="flex justify-center text-xs text-white align-items-center">' . $size . '</div>
    //             </div>
    //             <img style="height: 30px;" src="' . $related[0]->url . '"/>
    //         </div>';
    // }

    public function sanitizeRow($row): \stdClass
    {
        /**
         * Convert stdClass to array.
         */
        $tempRow = json_decode(json_encode($row), true);

        foreach ($tempRow as $column => $value) {
            if (!is_string($tempRow[$column])) {
                continue;
            }

            if (is_array($value)) {
                return $this->sanitizeRow($tempRow[$column]);
            } else {
                $row->{$column} = strip_tags($value);
            }
        }

        return $row;
    }

    public function applyParams($row): \stdClass
    {
        foreach ($this->columns as $column) {
            if(!empty($column->params['cut'])){
                if(!empty($row->{$column->index})){
                    $row->{$column->index} = strip_tags($row->{$column->index});
                    $substr = mb_substr($row->{$column->index}, 0, $column->params['cut']);
                    if (strcmp($row->{$column->index}, $substr) !== 0) {
                        $row->{$column->index} = $substr . '...';
                    }
                }
            }
        }
        return $row;
    }
    

    public function resolveCommonData()
    {
        foreach ($this->data['records'] as &$record) {
            //dump($record);
            foreach ($this->package_columns as $config) {
                //dump($config);
                $key = $config['key'];

                if(!empty($config['vb'])){

                    //dd($config);

                    if (!empty($config['vb']['overrided'])) {
                        $override = true;
                        $override_value = trim($record->{$config['vb']['overrided']});

                        if(!$override_value){
                            $override = false;
                        } else {
                            if(!empty($config['vb']['active_field'])){
                                $active = $record->{$config['vb']['active_field']};
                                if(!$active){
                                    $override = false;
                                }
                            }
                        }
                        if($override){
                            $record->$key = $override_value;
                            if (!empty($config['vb']['variation'])) {
                                $record->$key .= '<span class="ml-2 text-pale">[V]</span>';
                            }
                            unset($record->{$config['vb']['base']});
                        } else {
                            $record->$key = trim($record->{$config['vb']['base']});    
                        }
                    }

                    if (!empty($config['vb']['preffered'])) {
                        unset($record->{$config['vb']['base']});
                    }
                }

                // if ($config['control'] == 'image') {
                //     $this->getThumbForRecord($record, $config);
                // }

                if (empty($record->$key) && !empty($config['if_empty'])) {
                    $record->$key = '<span class="text-pale">' . omniModuleTrans('default', 'datagrid.empty-variation') . '</span>';
                }
            }
        }
    }

    
}
// filters[categories][0]=1
// filters[categories][1]=18
// pagination[current_page]=1
// pagination[per_page]=10
// sort[column]=news.id
// sort[order]=desc
// current_channel_id=1
// current_locale_id=1
// init=