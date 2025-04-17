<?php

return [
    'module' => 'how_it_works',

    'title' => 'How It Works',
    'title_plural' => 'How It Works',

    'scheme' => [
        'table' => [
            'name' => 'how_it_works',
            'fields' => [
                'position' => 'position',
                'featured' => 'featured',
                'title' => 'title',
                'description' => 'description',
                'image' => [
                    'type' => 'images',
                    'relation' => [
                        'connect_table' => 'common',
                        'src_table' => 'images',
                    ],
                ],

            ],
        ],
    ],

    'methods' => [],

    'menu' => [
        'key' => 'how_it_works',
        'name' => 'How It Works',
        'route' => 'admin.how_it_works.index',
        'sort' => 450,
        'icon' => 'fa-solid fa-circle-question text-gray-500 dark:text-gray-300'
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
                    'width' => '50px',
                    'class' => 'justify-start text-gray-300 dark:text-gray-600',
                ]
            ],
            [
                'index' => 'active',
                'label' => 'Active',
                'type' => 'boolean',
                'searchable' => false,
                'filterable' => true,
                'sortable' => false,
                'control' => 'checkbox',
                'params' => [
                    'width' => '80px',
                    'class' => 'justify-center',
                ]
            ],
            [
                'index' => 'position',
                'label' => 'P',
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
                'index' => 'featured',
                'label' => 'Featured',
                'type' => 'boolean',
                'searchable' => false,
                'filterable' => true,
                'sortable' => false,
                'control' => 'checkbox',
                'params' => [
                    'width' => '80px',
                    'class' => 'justify-center',
                ]
            ],
            [
                'index' => 'title',
                'label' => ['how_it_works', 'title'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
                'if_empty' => 'Empty: click to add',
            ],
            [
                'index' => 'description',
                'label' => ['how_it_works', 'description'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
                'if_empty' => 'Empty: click to add',
                'params' => [
                    'cut' => 100,
                ]
            ],
            [
                'index' => 'image',
                'label' => ['how_it_works', 'datagrid.image'],
                'type' => 'image',
                'searchable' => false,
                'filterable' => false,
                'sortable' => false,
                'control' => 'image',
                'thumb_num' => 1,
                'params' => [
                    'width' => '100px',
                ]
            ]
        ],

    ],
    'editing' => [
        'handler' => 'vue',
        'columns' => [
            [
                'classes' => 'flex flex-col w-full',
                'blocks' => [
                    [
                        'title' => ['faq', 'edit_form.block_title.main'],
                        'fields' => [
                            [
                                'key' => 'title',
                                'type' => 'html',
                                'control' => 'tiny',
                                'title' => ['how_it_works', 'title'],
                            ],
                            [
                                'key' => 'description',
                                'type' => 'html',
                                'control' => 'tiny',
                                'title' => ['how_it_works', 'description'],
                            ],
                            [
                                'key' => 'image',
                                'type' => 'image',
                                'control' => 'img',
                                'multiple' => false,
                                'title' => ['how_it_works', 'edit_form.image'],
                            ],
                        ]
                    ],
                ]
            ],

        ]
    ],


];
