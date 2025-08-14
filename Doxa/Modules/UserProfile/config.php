<?php
return [
    'module' => 'user_profile',

    'title' => 'User Profile',
    'title_plural' => 'User Profiles',

    'scheme' => [
        'table' => [
            'name' => 'user_profiles',
            'fields' => [
                'id' => 'id',
                'status' => 'status',
                'user' => 'user',
                'role_id' => 'role_id',
                'role_name' => 'role_name',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
                'name' => 'name',
                'info' => 'info',
                'role' => [
                    'type' => 'related',
                    'relation' => [
                        'src_package' => 'profile_role',
                    ],
                ],
            ],
        ],
    ],

    'menu' => [
        'key' => 'user_profile',
        'name' => 'User Profiles',
        'route' => 'admin.user_profile.index',
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
            // [
            //     'index' => 'status',
            //     'label' => ['user_profile', 'datagrid.status'],
            //     'type' => 'integer',
            //     'searchable' => false,
            //     'filterable' => true,
            //     'sortable' => true,
            //     'variation' => true,
            //     'control' => 'int',
            //     'params' => [
            //         'width' => '50px',
            //         'class' => 'justify-center',
            //     ]
            // ],
            [
                'index' => 'role_name',
                'label' => 'Role Name',
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
                'params' => [
                    'width' => '30px',
                ]
            ],
            [
                'index' => 'role_id',
                'label' => 'Role ID',
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
                'index' => 'user_id',
                'label' => 'User ID',
                'type' => 'integer',
                'searchable' => false,
                'filterable' => false,
                'sortable' => true,
                'control' => 'string',
                'params' => [
                    'width' => '30px',
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
                        'title' => ['user_profile', 'edit_form.block_title.main'],
                        'fields' => [
                            [
                                'key' => 'status',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['user_profile', 'edit_form.status'],
                            ],
                            [
                                'key' => 'name',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['user_profile', 'edit_form.name'],
                                'validation_rules' => [
                                    'string',
                                    'max:200',
                                    'required',
                                ]
                            ],
                            [
                                'key' => 'user_id',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['user_profile', 'edit_form.user_id'],
                                'validation_rules' => [
                                ]
                            ],
                            [
                                'key' => 'description',
                                'type' => 'text',
                                'control' => 'textarea',
                                'title' => ['user_profile', 'edit_form.description'],
                                'validation_rules' => [
                                    'string',
                                    'max:500',
                                ]
                            ],

                        ],
                    ],

                ]
            ],
        ]
    ]
];
