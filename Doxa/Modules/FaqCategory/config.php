<?php

return [
    'module' => 'faq_category',

    'title' => 'Faq category',
    'title_plural' => 'Faq categories',

    'scheme' => [
        'table' => [
            'name' => 'faq_categories',
            'fields' => [
                'key' => 'key',
                'position' => 'position',
            ],
        ],
        'variations_table' => [
            'name' => 'faq_category_variations',
            'fields' => [
                'title' => 'title',
                'description' => 'description',
            ],
        ],
    ],

    'related_list' => [
        'fields' => [
            'id',
            'title',
            'key',
        ],
        
    ],

    'methods' => [],

    'menu' => [
        'key' => 'faq_category',
        'name' => 'FAQ Categories',
        'route' => 'admin.faq_category.index',
        'sort' => 455,
        'icon' => 'fa-solid fa-layer-group text-gray-500 dark:text-gray-300'
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
                'index' => 'title',
                'label' => ['faq_category', 'datagrid.title'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'control' => 'string',
                'variation' => true,
                'if_empty' => 'Empty: click to add',
            ],
            [
                'index' => 'key',
                'label' => ['faq_category', 'datagrid.key'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'variation' => false,
                'control' => 'string',
                'params' => [
                    'width' => '300px',
                ],
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
                        'variations_dates_vs_editor' => false,
                        'chanel_ws_locale_selector' => true,
                        'is_variations' => false,
                        'title' => ['faq', 'edit_form.block_title.main'],
                        'fields' => [
                            [
                                'key' => 'active',
                                'type' => 'checkbox',
                                'control' => 'checkbox',
                                'title' => ['faq_category', 'edit_form.active'],
                            ],
                            [
                                'key' => 'key',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['faq_category', 'edit_form.key'],
                                'validation_rules' => [
                                    'max:200',
                                    'regex:/^[a-zA-Z0-9\_\-\.]+$/u'
                                ],
                                'rule' => ['faq_category', 'edit_form.rules.key']
                            ],
                            [
                                'key' => 'title',
                                'type' => 'text',
                                'control' => 'input',
                                'variation' => true,
                                'title' => ['faq_category', 'edit_form.title'],
                            ],
                            [
                                'key' => 'description',
                                'type' => 'html',
                                'control' => 'tiny',
                                'variation' => true,
                                'title' => ['faq_category', 'edit_form.text'],
                            ],
                        ]
                    ],
                ]
            ],

        ]
    ],


];
