<?php
return [
    'module' => 'social',

    'title' => 'social',
    'title_plural' => 'social',

    'scheme' => [
        'table' => [
            'name' => 'social',
            'fields' => [
                'link' => 'link',
                'title' => 'title',
                'icon' => [
                    'type' => 'images',
                    'relation' => [
                        'src_table' => 'images',
                    ],
                ],
            ],
        ],
    ],

    'menu' => [
        'key' => 'social',
        'name' => 'Social',
        'route' => 'admin.social.index',
        'sort' => 190,
        'icon' => 'fa-solid fa-square-share-nodes text-gray-500 dark:text-gray-300'
    ],

    'datagrid' => [
        'handler' => 'vue',
        'sortable_by_position' => true,
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
                'index' => 'position',
                'label' => ['default', 'datagrid.order'],
                'type' => 'integer',
                'searchable' => false,
                'filterable' => false,
                'sortable' => true,
                'control' => 'position',
                'params' => [
                    'width' => '50px',
                    'class' => 'justify-center',
                ]
            ],
            [
                'index' => 'active',
                'label' => ['default', 'datagrid.active'],
                'type' => 'boolean',
                'searchable' => false,
                'filterable' => true,
                'sortable' => false,
                'control' => 'checkbox',
                'params' => [
                    'width' => '50px',
                    'class' => 'justify-center',
                ]
            ],
            [
                'index' => 'title',
                'label' => ['default', 'datagrid.title'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
                'if_empty' => 'Empty: click to add',
                'params' => [
                    'width' => '200px',
                ],
            ],
            [
                'index' => 'link',
                'label' => ['default', 'datagrid.link'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'base' => true,
                'control' => 'string',
                
            ],
        ]
    ],
    'editing' => [
        'handler' => 'vue',
        'columns' => [
            [
                'classes' => 'flex flex-col flex-1 gap-2 mr-3 max-xl:flex-auto',
                'blocks' => [
                    [
                        'title' => ['social', 'edit_form.block_title.main'],
                        'fields' => [
                            [
                                'key' => 'active',
                                'type' => 'checkbox',
                                'control' => 'checkbox',
                                'title' => ['social', 'edit_form.active'],
                            ],
                            [
                                'key' => 'title',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['social', 'edit_form.title'],
                                'validation_rules' => [
                                    'required',
                                ]
                            ],
                            [
                                'key' => 'link',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['social', 'edit_form.link'],
                                'validation_rules' => [
                                    'required',
                                ]
                            ],
                            [
                                'key' => 'icon',
                                'type' => 'image',
                                'control' => 'img',
                                'multiple' => false,
                                'title' => ['social', 'edit_form.icon'],
                            ],
                            [
                                'key' => 'font_icon',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => 'Font icon classes',
                            ],
                        ],
                    ],
                ]
            ],
            
        ]
    ]
];