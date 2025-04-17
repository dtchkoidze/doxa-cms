<?php
return [
    'module' => 'static_page',

    'title' => 'page',
    'title_plural' => 'pages',

    'sitemap' => [
        'enabled' => true
    ],

    'scheme' => [
        'table' => [
            'name' => 'static_pages',
            'fields' => [
                'url_key' => 'url_key',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ],
        ],
        'variations_table' => [
            'name' => 'static_page_variations',
            'fields' => [
                'title' => 'title',
                'description' => 'description',
                'description_short' => 'description_short',

                'meta_title' => 'meta_title',
                'meta_description' => 'meta_description',
                'meta_keywords' => 'meta_keywords',

                'created_at' => 'created_at',
                'updated_at' => 'updated_at',

                'admin_id' => 'admin_id',

            ],
        ],  
    ],

    'menu' => [
        'key' => 'static_page',
        'name' => 'Pages',
        'route' => 'admin.static_page.index',
        'sort' => 120,
        'icon' => 'fa-solid fa-table-columns text-gray-500 dark:text-gray-300'
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
                    'class' => 'justify-start text-gray-300 dark:text-gray-600',
                ]
            ],
            [
                'index' => 'active',
                'label' => ['static_page', 'datagrid.active'],
                'type' => 'boolean',
                'searchable' => false,
                'filterable' => true,
                'sortable' => false,
                'variation' => true,
                'control' => 'checkbox',
                'params' => [
                    'width' => '50px',
                    'class' => 'justify-center',
                ]
            ],
            [
                'index' => 'title',
                'label' => ['static_page', 'datagrid.title'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'variation' => true,
                'control' => 'string',
                'if_empty' => 'Empty: click to add',
            ],
            // [
            //     'index' => 'created_at',
            //     'label' => ['static_page', 'datagrid.created_at'],
            //     'type' => 'date_range',
            //     'searchable' => true,
            //     'filterable' => true,
            //     'sortable' => true,
            //     'variation' => true,
            //     'control' => 'string',
            // ],
            [
                'index' => 'url_key',
                'label' => ['static_page', 'datagrid.url_key'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'variation' => false,
                'base' => true,
                'control' => 'string',
                'params' => [
                    'width' => '300px',
                ],
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
                        'title' => ['static_page', 'edit_form.block_title.main'],
                        'chanel_ws_locale_selector' => true,
                        'variations_dates_vs_editor' => true,
                        'fields' => [
                            [
                                'key' => 'active',
                                'variation' => true,
                                'type' => 'checkbox',
                                'control' => 'checkbox',
                                'title' => ['static_page', 'edit_form.active'],
                            ],
                            [
                                'key' => 'title',
                                'variation' => true,
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['static_page', 'edit_form.title'],
                                'validation_rules' => [
                                    'string',
                                    'max:200',
                                    'requiredIf::active,1'
                                ]
                            ],
                            [
                                'key' => 'description',
                                'variation' => true,
                                'type' => 'html',
                                'control' => 'tiny',
                                'title' => ['static_page', 'edit_form.description'],
                                'validation_rules' => [

                                ]
                            ],
                            [
                                'key' => 'description_short',
                                'variation' => true,
                                'type' => 'html',
                                'control' => 'tiny',
                                'title' => ['static_page', 'edit_form.description_short'],
                            ],
                        ],
                    ],
                    [
                        'title' => ['static_page', 'edit_form.block_title.meta'],
                        'fields' => [
                            [
                                'key' => 'meta_title',
                                'variation' => true,
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['static_page', 'edit_form.meta_title'],
                            ],
                            [
                                'key' => 'meta_description',
                                'variation' => true,
                                'type' => 'text',
                                'control' => 'textarea',
                                'title' => ['static_page', 'edit_form.meta_description'],
                            ],
                            [
                                'key' => 'meta_keywords',
                                'variation' => true,
                                'type' => 'text',
                                'control' => 'textarea',
                                'title' => ['static_page', 'edit_form.meta_keywords'],
                            ],
                        ],
                    ],
                ]
            ],
            [
                'classes' => 'flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full',
                'blocks' => [
                    [
                        'fields' => [
                            [
                                'key' => 'url_key',
                                'type' => 'text',
                                'control' => 'input',
                                'generate_from' => 'title',
                                'title' => ['static_page', 'edit_form.url_key'],
                                'validation_rules' => [
                                    'required',
                                    'max:200',
                                    'regex:/^[a-zA-Z0-9\_\.\-]+$/u'
                                ]
                            ],
                        ]
                    ],
                ]
            ]
        ]
    ]
];