<?php

return [
    'module' => 'faq',

    'title' => 'faq',
    'title_plural' => 'faq',

    'scheme' => [
        'table' => [
            'name' => 'faq',
            'fields' => [
                'key' => 'key',
                'position' => 'position',
                'featured' => 'featured',
                'category' => [
                    'type' => 'relation',
                    'relation' => [
                        'src_package' => 'faq_category',
                    ],
                ],
            ],
        ],
        'variations_table' => [
            'name' => 'faq_variations',
            'fields' => [
                'question' => 'question',
                'answer' => 'answer',
            ],
        ],
    ],

    'methods' => [],

    'menu' => [
        'key' => 'faq',
        'name' => 'FAQ',
        'route' => 'admin.faq.index',
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
                'label' => 'A',
                'type' => 'boolean',
                'searchable' => false,
                'filterable' => true,
                'sortable' => false,
                'control' => 'checkbox',
                'variation' => true,
                'params' => [
                    'width' => '50px',
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
                'index' => 'question',
                'label' => ['faq', 'question'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
                'variation' => true,
                'if_empty' => 'Empty: click to add',
                'params' => [
                    'cut' => 100,
                ]
            ],
            [
                'index' => 'answer',
                'label' => ['faq', 'answer'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
                'variation' => true,
                'if_empty' => 'Empty: click to add',
                'params' => [
                    'cut' => 100,
                ]
            ],
            [
                'index' => 'category',
                'label' => ['faq', 'category'],
                'type' => 'related',
                'searchable' => false,
                'filterable' => true,
                'sortable' => false,
                'control' => 'related',
                'params' => [
                    'width' => '200px',
                ]
            ],
        ],

    ],
    'editing' => [
        'handler' => 'vue',
        'columns' => [
            [
                'classes' => 'flex flex-col w-full',
                'blocks' => [
                    [
                        //'variations_dates_vs_editor' => false,
                        'chanel_ws_locale_selector' => true,
                        'is_variations' => false,
                        'title' => ['faq', 'edit_form.block_title.main'],
                        'fields' => [
                            [
                                'key' => 'active',
                                'type' => 'checkbox',
                                'control' => 'checkbox',
                                'variation' => true,
                                'title' => ['faq', 'edit_form.active'],
                            ],
                            [
                                'key' => 'question',
                                'type' => 'html',
                                'control' => 'tiny',
                                'variation' => true,
                                'title' => ['faq', 'question'],
                            ],
                            [
                                'key' => 'answer',
                                'type' => 'html',
                                'control' => 'tiny',
                                'variation' => true,
                                'title' => ['faq', 'answer'],
                            ],
                            [
                                'key' => 'category',
                                'type' => 'related',
                                'module' => 'faq_category',
                                'control' => 'related',
                                'title' => ['faq', 'category'],
                                'validation_rules' => [
                                    'required',
                                ],
                            ],
                        ]
                    ],
                ]
            ],

        ]
    ],


];
