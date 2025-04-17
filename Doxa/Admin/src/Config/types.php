<?php

return [
    'aliases' => [
        'id' => [
            'type' => 'bigint',
            'extra' => 'auto_increment',
            'is_nullable' => false,
            'default_value' => null,
        ],
        'name' => [
            'type' => 'varchar',
            'length' => 100,
            'is_nullable' => false,
            'default_value' => null,
        ]
    ],
];
