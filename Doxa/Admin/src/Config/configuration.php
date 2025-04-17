<?php

return [
    'blocks' => [
        'General' => [
            'fields' => [
                [
                    'key' => 'asdasdasd',
                    'type' => 'checkbox',
                    'variation' => true,
                    'control' => 'checkbox',
                    'title' => 'Public Registration',
                ],
                [
                    'key' => 'site_name',
                    'type' => 'text',
                    'control' => 'input',
                    'title' => 'Website Name',
                    'validation_rules' => [
                        'string',
                        'max:20',
                        'min:5',
                    ],
                ],
                [
                    'key' => 'logo',
                    'type' => 'image',
                    'control' => 'img',
                    'title' => 'Company Logo',
                    'multiple' => false,
                ],
            ]
        ],
        'Mailing' => [
            'fields' => [
                [
                    'key' => 'logo',
                    'type' => 'image',
                    'control' => 'img',
                    'title' => 'Website Favicon',
                    'multiple' => false,
                    'validation_rules' => [
                        'between:10, 500',
                        'dimensions:ratio=1/1',
                        'mimes:jpg,bmp,png,svg'
                    ]
                ],
            ]
        ]
    ]
];
