<?php
return [
    'module' => 'slider',

    'title' => 'slider',
    'title_plural' => 'slider',

    'scheme' => [
        'table' => [
            'name' => 'slider',
            'fields' => [
                'key' => 'key',
                'image' => [
                    'type' => 'images',
                    'relation' => [
                        'src_table' => 'images',
                    ],
                ],
            ],
        ],
        'variations_table' => [
            'name' => 'slider_variations',
            'fields' => [
                'position' => 'position',

                'title' => 'title',
                'description' => 'description',

                'theme' => 'theme',

                'button_1_title' => 'button_1_title',
                'button_2_title' => 'button_2_title',
                'button_1_link' => 'button_1_link',
                'button_2_link' => 'button_2_link',
           
            ],
        ],  
    ],

    'menu' => [
        'key' => 'slider',
        'sort' => 230,
        'name' => 'Slider',
        'route' => 'admin.slider.index',
        'icon' => 'fa-solid fa-arrows-left-right-to-line text-gray-500 dark:text-gray-300'
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
                    'width' => '50px',
                    'class' => 'justify-start text-gray-300 dark:text-gray-600',
                ]
            ],
            [
                'index' => 'position',
                'label' => ['slider', 'datagrid.order'],
                'type' => 'integer',
                'searchable' => false,
                'filterable' => false,
                'sortable' => true,
                'control' => 'position',
                'variation' => true,
                'params' => [
                    'width' => '50px',
                    'class' => 'justify-center',
                ]
            ],
            [
                'index' => 'active',
                'label' => ['slider', 'datagrid.active'],
                'type' => 'boolean',
                'searchable' => false,
                'filterable' => true,
                'sortable' => true,
                'variation' => true,
                'control' => 'checkbox',
                'params' => [
                    'width' => '50px',
                    'class' => 'justify-center',
                ]
            ],
            [
                'index' => 'title',
                'label' => ['slider', 'datagrid.title'],
                'type' => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable' => true,
                'variation' => true,
                'control' => 'string',
                'if_empty' => 'Empty: click to add',
                'params' => [
                    'cut' => 100,
                ]
            ],
            [
                'index' => 'image',
                'label' => ['news', 'datagrid.image'],
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
        'sortable_by_position' => true,
        'disable_pagination' => true,
    ],
    'editing' => [
        'handler' => 'vue',
        'columns' => [
            [
                'classes' => 'flex flex-col flex-1 gap-2 max-xl:flex-auto',
                'blocks' => [
                    [
                        'title' => ['slider', 'block_title.description'],
                        'chanel_ws_locale_selector' => true,
                        'fields' => [
                            [
                                'key' => 'active',
                                'variation' => true,
                                'type' => 'checkbox',
                                'control' => 'checkbox',
                                'title' => ['slider', 'edit_form.active'],
                            ],
                            [
                                'key' => 'title',
                                'variation' => true,
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['slider', 'edit_form.title'],
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
                                'title' => ['slider', 'edit_form.description'],
                                'validation_rules' => [
                                    'requiredIf::active,1'
                                ]
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
                                'key' => 'key',
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['slider', 'edit_form.key'],
                                'validation_rules' => [
                                    'required',
                                    'max:100',
                                    'regex:/^[a-zA-Z0-9\_]+$/u',
                                ]
                            ],
                            [
                                'key' => 'image',
                                'type' => 'image',
                                'control' => 'img',
                                'title' => ['slider', 'edit_form.image'],
                                // 'validation_rules' => [
                                //     'required'
                                // ]
                            ],
                            [
                                'key' => 'theme',
                                'variation' => true,
                                'type' => 'text',
                                'control' => 'input',
                                'title' => ['slider', 'edit_form.theme'],
                                'validation_rules' => [
                                    'string',
                                    'max:100'
                                ]
                            ],
                            [
                                'key' => 'theme_icon',
                                'type' => 'image',
                                'control' => 'img',
                                'multiple' => false,
                                'title' => ['slider', 'edit_form.theme_icon'],
                            ],
                            [
                                'key' => 'data',
                                'type' => 'text',
                                'control' => 'textarea',
                                'title' => ['slider', 'edit_form.additional_data'],
                            ],
                        ]
                    ],
                ]
            ]
        ]
    ]
];