<?php
return [
    'module' => 'channel',

    'title' => 'Channel',
    'title_plural' => 'Channels',

    'default_channel_only' => true,
    
    'scheme' => [
        'table' => [
            'name' => 'channels',
            'fields' => [
                'name' => [
                    'type' => 'text',
                ],
                // 'code' => [
                //     'type' => 'varchar',
                //     'length' => 255,
                // ],
                'host' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'locales' => [
                    'type' => 'related',
                    'relation' => [
                        'connect_table' => 'locale_connects',
                        'columns' => [
                            'connect_id',
                            'connect_type',
                            'connect_key',
                            'src_id',
                        ],
                        'src_package' => 'locale',
                    ],
                ],
            ],
        ],
    ],

    'menu' => [
        'key' => 'channel',
        'name' => 'Channels',
        'route' => 'admin.channel.index',
        'sort' => 10000,
        'icon' => 'fa-solid fa-globe text-gray-500 dark:text-gray-300'
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
                'label' => ['channel', 'datagrid.active'],
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
                'label' => ['channel', 'datagrid.name'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
                'params' => [
                    'width' => '300px',
                ]
            ],
            // [
            //     'index' => 'code',
            //     'label' => ['channel', 'datagrid.code'],
            //     'type' => 'string',
            //     'searchable' => true,
            //     'filterable' => true,
            //     'sortable' => true,
            //     'control' => 'string',
            //     'params' => [
            //         'width' => '300px',
            //     ]
            // ],
            [
                'index' => 'host',
                'label' => ['channel', 'datagrid.host'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',

            ],
        ],
        'disable_pagination' => true,
    ],
    'editing' => [
        'columns' => [
            [
                'classes' => 'flex flex-col flex-1 gap-2 max-xl:flex-auto',
                'blocks' => [
                    [
                        'title' => ['channel', 'edit_form.block_title.main'],
                        'fields' => [
                            [
                                'key' => 'active',
                                //'variation' => true,
                                'type' => 'checkbox',
                                'control' => 'checkbox',
                                'title' => ['channel', 'edit_form.active'],
                            ],
                            [
                                'key' => 'name',
                                //'variation' => true,
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['channel', 'edit_form.name'],
                                'validation_rules' => [
                                    'string',
                                    'max:200',
                                    //'required',
                                ]
                            ],
                            // [
                            //     'key' => 'code',
                            //     //'variation' => true,
                            //     'type' => 'text',
                            //     'control' => 'input',
                            //     'title' => ['channel', 'edit_form.code'],
                            //     'validation_rules' => [
                            //         'string',
                            //         'max:200',
                            //         //'required',
                            //     ]
                            // ],
                            [
                                'key' => 'host',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['channel', 'edit_form.host'],
                                'validation_rules' => [
                                    'required',
                                    'regex:/^[a-zA-Z0-9\_\-\/\.]+$/u'
                                ]
                            ],
                            [
                                'key' => 'locales',
                                'type' => 'related',
                                'control' => 'related',
                                'title' => ['channel', 'edit_form.locales'],
                                'multiple' => true,
                                'module' => 'locale',
                            ],
                        ],
                    ],

                ]
            ],
        ]
    ]
];


