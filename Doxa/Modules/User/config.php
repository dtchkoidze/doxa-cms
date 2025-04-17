<?php
return [
    'module' => 'users',

    'title' => 'User',
    'title_plural' => 'Users',

    'scheme' => [
        'table' => [
            'name' => 'users',
            'fields' => [
                'id' => 'id',
                'email' => 'email',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
                'name' => 'name',
            ],
        ],
    ],

    'related_list' => [
        'fields' => [
            'id',
            'email',
            'name',
        ],
        
    ],

    'menu' => [
        'key' => 'user',
        'name' => 'Users',
        'route' => 'admin.user.index',
        'sort' => 10000,
        'icon' => 'fa-solid fa-user text-gray-500 dark:text-gray-300'
    ],
    'datagrid' => [
        'handler' => 'vue',
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
                'label' => ['default', 'datagrid.active'],
                'type' => 'boolean',
                'searchable' => false,
                'filterable' => true,
                'sortable' => false,
                'variation' => true,
                'control' => 'checkbox',
                'params' => [
                    'width' => '50px',
                    'class' => 'justify-center',
                ]
            ],
        ],
    ],
    'editing' => [
        'columns' => [
            [
                'classes' => 'flex flex-col flex-1 gap-2 mr-3 max-xl:flex-auto',
                'blocks' => [
                    [
                        'title' => ['default', 'edit_form.block_title.main'],
                        'fields' => [
                            [
                                'key' => 'active',
                                'type' => 'checkbox',
                                'control' => 'checkbox',
                                'title' => ['default', 'edit_form.active'],
                            ],
                            [
                                'key' => 'email',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => 'Email',
                                'validation_rules' => [
                                    'required',
                                    'string',
                                    'max:200',
                                ]
                            ],

                        ],
                    ],

                ]
            ],
        ]
    ]
];
