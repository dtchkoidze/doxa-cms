1. Relation::MODE_WITHOUT_CONNECT_TABLE = 'without_connect';

Example:

'feedback' => [
    'type' => 'related',
    'relation' => [
        'src_package' => 'feedback',
    ],
],

<? php
$info = [ 
  "type" => "related",
  "key" => "feedback",
  "mode" => "without_connect",
  "src_package" => "feedback",
  "src_table" => null,
  "base|variation" => "base",
  "options" => [],
  "connect_table" => null,
  "columns" => [
    "src_id_column" => null,
    "src_type_column" => null,
    "src_key_column" => null,
    "src_package_column" => null,
    "connect_id_column" => null,
    "connect_type_column" => null,
    "connect_key_column" => null,
    "connect_package_column" => null,
  ],
  "COLUMN connect_id" => null,
  "COLUMN connect_type" => null,
  "COLUMN connect_key" => null,
  "COLUMN connect_package" => null,
  "COLUMN src_id" => null,
  "COLUMN src_type" => null,
  "COLUMN src_key" => null,
  "error" => [],
];