<?php
return [
    'module' => 'locale',
    'default_channel_only' => true,
    'scheme' => [
        'table' => [
            'name' => 'locales',
            'fields' => [
                'name' => [
                    'type' => 'text',
                ],
                'code' => [
                    'type' => 'varchar',
                    'length' => 10
                ]
            ],
        ],
    ],

    'related_list' => [
        'fields' => [
            'id',
            'name',
            'code',
            'default',
        ],

    ],

    'menu' => [
        'key' => 'locale',
        'name' => 'Locales',
        'route' => 'admin.locale.index',
        'sort' => 10000,
        'icon' => 'fa-solid fa-language text-gray-500 dark:text-gray-300'
    ],
    'datagrid' => [
        'columns' => [
            [
                'index' => 'id',
                'label' => 'ID',
                'type' => 'integer',
                'searchable' => false,
                'filterable' => false,
                'sortable' => true,
                'control' => 'string',
                'params' => [
                    'width' => '30px',
                ]
            ],
            [
                'index' => 'active',
                'label' => ['locale', 'datagrid.active'],
                'type' => 'boolean',
                'searchable' => false,
                'filterable' => true,
                'sortable' => true,
                //'variation' => true,
                'control' => 'checkbox',
                'params' => [
                    'width' => '50px',
                    'class' => 'justify-center',
                ]
            ],
            [
                'index' => 'name',
                'label' => ['locale', 'datagrid.name'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
            ],
            [
                'index' => 'code',
                'label' => ['locale', 'datagrid.code'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
                'params' => [
                    'width' => '100px',
                ]
            ],
        ],
        'disable_pagination' => true,
    ],
    'editing' => [
        'columns' => [
            [
                'classes' => 'flex flex-col flex-1 gap-2 mr-3 max-xl:flex-auto',
                'blocks' => [
                    [
                        'title' => ['locale', 'edit_form.block_title.main'],
                        'fields' => [
                            [
                                'key' => 'active',
                                //'variation' => true,
                                'type' => 'checkbox',
                                'control' => 'checkbox',
                                'title' => ['locale', 'edit_form.active'],
                            ],
                            [
                                'key' => 'name',
                                //'variation' => true,
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['locale', 'edit_form.name'],
                                'validation_rules' => [
                                    'string',
                                    'max:200',
                                    'required',
                                ]
                            ],
                            [
                                'key' => 'code',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['channel', 'edit_form.code'],
                                'validation_rules' => [
                                    'required',
                                    'regex:/^[a-zA-Z]+$/u'
                                ]
                            ],
                        ],
                    ],

                ]
            ],
        ]
    ]
];