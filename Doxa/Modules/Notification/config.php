<?php
return [

    'module' => 'notification',

    'title' => 'Notification',
    'title_plural' => 'Notifications',

    'ignore_channels' => true,
    
    'cache' => true,

    'scheme' => [
        'table' => [
            'name' => 'notifications',
            'fields' => [
                'active' => 'active',
                'name' => 'name',
                'key' => 'key',
                'type' => 'type',
            ],
        ],
        'variations_table' => [
            'name' => 'notification_variations',
            'fields' => [
                'title' => 'title',
                'text' => 'text',
                
            ],
        ],  
    ],

    'menu' => [
        'key' => 'notification',
        'name' => 'Notifications',
        'route' => 'admin.notification.index',
        'sort' => 9000,
        'icon' => 'fa-solid fa-bell text-gray-500 dark:text-gray-300'
    ],
    'datagrid' => [
        'pagination' => [
            'per_page' => 50,
            'page' => 1
        ],
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
                    'class' => 'justify-start text-gray-300 dark:text-gray-600',
                ]
            ],
            [
                'index' => 'key',
                'label' => ['notification', 'key'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
                // 'params' => [
                //     'width' => '300px'
                // ]

            ],
            [
                'index' => 'name',
                'label' => ['notification', 'name'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
            ],
            [
                'index' => 'type',
                'label' => ['notification', 'notification.type'],
                'type' => 'integer',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
                'params' => [
                    'width' => '100px'
                ]
            ],
        ],
    ],
    'editing' => [
        'handler' => 'vue',
        'columns' => [
            [
                'classes' => 'flex flex-col flex-1 gap-2 max-xl:flex-auto',
                'blocks' => [
                    [
                        'title' => ['notification','edit_form.block_title.main'],
                        'chanel_ws_locale_selector' => true,
                        'is_variations' => true,
                        'fields' => [
                            [
                                'key' => 'active',
                                'type' => 'checkbox',
                                'control' => 'checkbox',
                                'title' => 'Active',
                            ],
                            [
                                'key' => 'id',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => 'ID',
                                'validation_rules' => [
                                    'required',
                                    'unique:notifications',
                                    'numeric',
                                ]
                            ],
                            [
                                'key' => 'key',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['notification', 'key'],
                                'rule' => ['notification', 'rules.key'],
                                'validation_rules' => [
                                    'required',
                                    'max:200',
                                    'regex:/^[a-zA-Z0-9\._]+$/u'
                                ]
                            ],
                            [
                                'key' => 'name',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['notification', 'name'],
                            ],
                            [
                                'key' => 'type',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['notification', 'notification.type'],
                                'rule' => ['notification', 'rules.type'],
                                'validation_rules' => [
                                    'required',
                                    'max:1',
                                    'regex:/^[0-9]+$/u'
                                ]
                            ],
                            [
                                'key' => 'title',
                                'variation' => true,
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['notification', 'edit_form.title'],
                                'validation_rules' => [
                                    'string',
                                    'max:200',
                                    'requiredIf::active,1'
                                ],
                                'required' => ['service', 'edit_form.required.if_active']
                            ],
                            [
                                'key' => 'text',
                                'variation' => true,
                                'type' => 'html',
                                'control' => 'tiny',
                                'title' => ['notification', 'edit_form.text'],
                            ],
                        ]
                    ],
                ]
            ],
        ]
    ],
];
