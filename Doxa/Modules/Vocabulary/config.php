<?php
return [

    'module' => 'vocabulary',

    'title' => 'Vocabulary',
    'title_plural' => 'Vocabulary',

    'ignore_channels' => true,
    
    'cache' => true,

    'scheme' => [
        'table' => [
            'name' => 'vocabulary',
            'fields' => [
                'key' => 'key',
            ],
        ],
        'variations_table' => [
            'name' => 'vocabulary_variations',
            'fields' => [
                'text' => 'text',
            ],
        ],  
    ],

    'menu' => [
        'key' => 'vocabulary',
        'name' => 'Vocabulary',
        'route' => 'admin.vocabulary.index',
        'sort' => 9000,
        'icon' => 'fa-solid fa-book text-gray-500 dark:text-gray-300'
    ],
    'datagrid' => [
        'pagination' => [
            'per_page' => 10,
            'page' => 1
        ],
        'columns' => [
            // [
            //     'index' => 'id',
            //     'label' => 'ID',
            //     'type' => 'integer',
            //     'searchable' => false,
            //     'filterable' => false,
            //     'sortable' => true,
            //     'control' => 'string',
            //     'variation' => true,
            //     'params' => [
            //         'width' => '50px',
            //         'class' => 'justify-start text-gray-300 dark:text-gray-600',
            //     ]
            // ],
            [
                'index' => 'key',
                'label' => ['vocabulary', 'datagrid.key'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
                'params' => [
                    'width' => '300px'
                ]

            ],
            [
                'index' => 'text',
                'label' => ['vocabulary', 'datagrid.text'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'variation' => true,
                'control' => 'string',
                'if_empty' => 'Empty: click Edit to add',
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
                        'title' => ['vocabulary','edit_form.block_title.main'],
                        'chanel_ws_locale_selector' => true,
                        'is_variations' => true,
                        'fields' => [
                            [
                                'key' => 'key',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['vocabulary', 'edit_form.key'],
                                'rule' => ['vocabulary', 'edit_form.rules.key'],
                                'validation_rules' => [
                                    'required',
                                    'max:200',
                                    'regex:/^[a-zA-Z0-9\._]+$/u'
                                ]
                            ],
                            [
                                'key' => 'text',
                                'variation' => true,
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['vocabulary', 'edit_form.text'],
                                'validation_rules' => [
                                    'string',
                                    'max:200',
                                ],
                            ],
                        ]
                    ],
                ]
            ],
        ]
    ],
];
