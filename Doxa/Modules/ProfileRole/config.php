<?php
return [
    'module' => 'profile_role',
    'scheme' => [
        'table' => [
            'name' => 'profile_roles',
            'fields' => [
                'position' => 'position',
                'name' => [
                    'type' => 'varchar',
                ],
                'title' => [
                    'type' => 'text',
                ]
            ],
        ],
    ],

    'related_list' => [
        'fields' => [
            'id',
            'name',
            'title',
        ],
    ],

    'menu' => [
        'key' => 'profile_role',
        'name' => 'Profile Roles',
        'route' => 'admin.profile_role.index',
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
                'label' => ['default', 'datagrid.active'],
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
                'index' => 'position',
                'label' => 'P',
                'type' => 'boolean',
                'searchable' => false,
                'filterable' => true,
                'sortable' => true,
                'control' => 'position',
                'params' => [
                    'width' => '50px',
                    'class' => 'justify-center',
                ]
            ],
            [
                'index' => 'name',
                'label' => ['default', 'datagrid.name'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
            ],
            [
                'index' => 'title',
                'label' => ['default', 'datagrid.title'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
            ],
        ],
        'sortable_by_position' => true,
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
                                'type' => 'checkbox',
                                'control' => 'checkbox',
                                'title' => ['default', 'edit_form.active'],
                            ],
                            [
                                'key' => 'name',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['default', 'edit_form.name'],
                                'validation_rules' => [
                                    'string',
                                    'max:20',
                                    'required',
                                    'regex:/^[a-zA-Z]+$/u',
                                ]
                            ],
                            [
                                'key' => 'title',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['default', 'edit_form.title'],
                                'validation_rules' => [
                                    'required',
                                ]
                            ],
                        ],
                    ],

                ]
            ],
        ]
    ]
];