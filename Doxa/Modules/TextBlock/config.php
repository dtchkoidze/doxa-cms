<?php
return [

    'module' => 'text_block',

    'title' => 'Text Block',
    'title_plural' => 'Text Blocks',

    'ignore_channels' => true,

    'cache' => true,

    'scheme' => [
        'table' => [
            'name' => 'text_blocks',
            'fields' => [
                'key' => 'key',
            ],
        ],
        'variations_table' => [
            'name' => 'text_block_variations',
            'fields' => [
                'text' => 'text',
            ],
        ],  
    ],

    'menu' => [
        'key' => 'text_block',
        'name' => 'Text Blocks',
        'route' => 'admin.text_block.index',
        'sort' => 9100,
        'icon' => 'fa-solid fa-text-height text-gray-500 dark:text-gray-300',
        'params' => 'text_block'
    ],
    'datagrid' => [
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
                'label' => ['text_block', 'datagrid.key'],
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
                'label' => ['text_block', 'datagrid.text'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => false,
                'sortable' => false,
                'variation' => true,
                'control' => 'string',
                'if_empty' => 'Empty: click Edit to add',
                'params' => [
                    'cut' => 100,
                ]

            ],
        ],
    ],
    'editing' => [
        'columns' => [
            [
                'classes' => 'flex flex-col flex-1 gap-2 max-xl:flex-auto',
                'blocks' => [
                    [
                        'title' => ['text_block','edit_form.block_title.main'],
                        'chanel_ws_locale_selector' => true,
                        'is_variations' => true,
                        'fields' => [
                            [
                                'key' => 'key',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['text_block', 'edit_form.key'],
                                'rule' => ['text_block', 'edit_form.rules.key'],
                                'validation_rules' => [
                                    'max:200',
                                    'regex:/^[a-zA-Z0-9\.\-\_]+$/u',
                                ]
                            ],
                            [
                                'key' => 'text',
                                'variation' => true,
                                'type' => 'text',
                                'control' => 'tiny',
                                'title' => ['text_block', 'edit_form.text'],
                            ],
                        ]
                    ],
                ]
            ],
        ]
    ],
];
