<?php

namespace Doxa\Admin\Libraries\Configuration;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Doxa\Admin\Libraries\Configuration\Services\Parser;
use Illuminate\Support\Facades\Validator;

class Configuration
{
    private $table_name = 'configuration';
    private $cache_prefix = 'conf';
    private $data_rows;
    private $type_field_names = ['integer', 'text', 'timestamp'];
    private $values;
    private $db_general_table_name  = 'configuration';
    private $corresponding_types = [
        'checkbox' => 'integer',
        'datetime' => 'timestamp',
        'textarea' => 'text',
    ];

    public function __construct(protected Parser $parser)
    {
        $this->data_rows = $this->parser->getParsed();
    }

    public function getMenu()
    {
        return [
            [
                'key' => 'configuration',
                'name' => 'Configuration',
                'route' => 'admin.settings.configuration',
                'sort' => 1000,
                'icon' => 'fas fa-cogs text-gray-500 dark:text-gray-300',
            ]
        ];
    }

    public function register()
    {
        $config = config('admin.menu');
        $menu = $this->getMenu();
        $merged_config = collect($config)
            ->merge($menu)
            ->keyBy('key')
            ->toArray();

        Config::set('admin.menu', $merged_config);
    }


    public function getConfiguration()
    {
        $search_keys = $this->parser->getDBSearchKeys();
        $this->getValues($search_keys);
        $this->mergeValuesWithDataRows();
        return $this->data_rows;
    }

    public function get($key)
    {
        $item = $this->tryGetFromCacheOrDb($key);
        return $item;
    }

    public function saveConfiguration($payload)
    {
        $cols = array_keys($payload);

        $this->checkKeysFormat($cols);

        // $errors = $this->validateRequest($payload, $cols);

        $col_types = $this->parser->getColTypes($cols);

        foreach ($col_types as $col => $type) {
            $col_types[$col] = $this->checkForTypeInconsistency($type);
        }

        $errs = [];

        foreach ($col_types as $col => $type) {
            $method_name = 'save' . ucfirst($type);
            if (method_exists($this, $method_name)) {
                $saved = $this->$method_name($col, $payload[$col]);
                if (empty($saved)) {
                    $errs[] = $col;
                }
            } else {
                $saved = $this->saveValue($col, $payload[$col], $type);
                if (empty($saved)) {
                    $errs[] = $col;
                }
            }
        }

        if (count($errs) < 1) {
            return 1;
        } else {
            //TODO: correct logging
            dd($errs);
            return 0;
        }
    }

    private function validateRequest($payload, $cols)
    {
        $errors = [];
        foreach ($cols as $col) {
            if (!array_key_exists($col, $payload)) {
                continue;
            }

            $value = $payload[$col];
            $rules = $this->getValidationRulesForField($col);

            if (!empty($rules)) {
                $validator = Validator::make([$col => $value], [$col => $rules]);
                if ($validator->fails()) {
                    dd('fail');
                    $errors[$col] = $validator->errors()->first($col);
                }
            }
        }

        return $errors;
    }


    private function getValidationRulesForField($field_key)
    {
        $rules = [];

        foreach ($this->data_rows as $section) {
            foreach ($section['fields'] as $field) {
                $field_name = $section['name'] . '.' . $field['key'];
                if ($field_name == $field_key) {
                    if (isset($field['validation_rules'])) {
                        $rules = $field['validation_rules'];
                    }
                }
            }
        }

        return $rules;
    }

    private function checkKeysFormat(&$keys)
    {
        foreach ($keys as $index => $key) {
            if (!str_contains($key, '.')) {
                unset($keys[$index]);
            }
        }
    }


    private function tryGetFromCacheOrDb($key)
    {
        if (!empty($item = Cache::get($this->cache_prefix . '.' . $key))) {
            // dump('from cache');
            return $item;
        } else {
            $item = DB::table($this->db_general_table_name)->where('key', $key)->first();
            if (empty($item)) {
                return null;
            }
            $value = $this->getValue($item);
            if ($value) {
                Cache::forever($this->cache_prefix . '.' . $key, $value);
                return $value;
            }
        }
    }

    private function getValue($item)
    {
        $type = $this->getFinalType($item);
        if ($type) {
            if ($type == 'image') {
                return $this->getImage($item);
            }
            return $item->$type;
        } else {
            return null;
        }
    }

    private function getImage($item)
    {
        return Storage::url($item->image);
    }

    private function getFinalType($item)
    {
        $final_type = '';

        if (!empty($item->type)) {
            $final_type = $item->type;
        }

        if (!empty($config_type = $this->parser->getTypeFromConfig($item->key))) {
            $final_type = $config_type;
        }

        if (empty($final_type)) {
            $final_type = $this->findFirstNotEmptyField($item);
        }

        $final_type = $this->checkForTypeInconsistency($final_type);

        return $final_type;
    }


    private function findFirstNotEmptyField($item)
    {
        foreach ($item as $field_name => $value) {
            if (!empty($value) && in_array($field_name, $this->type_field_names)) {
                return $field_name;
            }
        }
    }

    private function saveValue($key, $value, $col)
    {
        try {
            $exists = DB::table($this->db_general_table_name)->where('key', $key)->exists();

            if ($exists) {
                $updated = DB::table($this->db_general_table_name)
                    ->where('key', $key)
                    ->update([$col => $value]);

                if ($updated) {
                    $this->updateKeyValueInCache($key, $value);
                    return 1;
                }
            } else {
                $inserted = DB::table($this->db_general_table_name)
                    ->insert(['key' => $key, $col => $value]);

                if ($inserted) {
                    $this->updateKeyValueInCache($key, $value);
                    return 1;
                }
            }

            return 1;
        } catch (\Exception $e) {
            return 0;
        }
    }


    private function updateKeyValueInCache($key, $val)
    {
        $key = $this->cache_prefix . '.' . $key;
        Cache::forever($key, $val);
    }

    private function saveInteger($col, $value)
    {
        $value = $value ? 1 : 0;
        return $this->saveValue($col, $value, 'integer');
    }

    private function saveText($col, $value)
    {
        return $this->saveValue($col, $value, 'text');
    }

    private function saveTimestamp($col, $value)
    {
        try {
            $value = Carbon::parse($value)->toDateTimeString();
        } catch (\Exception $e) {
            dd("Invalid timestamp format:", $e->getMessage());
        }

        return $this->saveValue($col, $value, 'timestamp');
    }

    private function getProjectImagesStorageFolder()
    {
        return ucfirst(config('app.project_name'));
    }

    private function deleteImage($key)
    {
        $record = DB::table('configuration')->where('key', $key)->first();
        $image = $record->image;
        if (empty($image)) {
            return;
        }

        Storage::delete($image);
        DB::table('configuration')
            ->where('key', $key)
            ->update(['image' => null]);
    }

    private function saveImage($key, $value)
    {
        if ($value == 'delete-image') {
            $this->deleteImage($key);
            return 1;
        }

        [$section_name, $field_key] = explode('.', $key, 2);

        $raw_images = $value;
        $bad_imgs = [];
        $saved_images = [];

        foreach ($raw_images as $file) {
            if (!$file instanceof UploadedFile) {
                $bad_imgs[] = $file;
                continue;
            }

            if (str_contains($file->getMimeType(), 'image')) {
                $hash_filename = md5(time() . Str::random(10));
                $base_path = "/images/{$this->table_name}/{$section_name}/{$field_key}/{$hash_filename}";

                if (str_contains($file->getMimeType(), 'svg')) {
                    $path = "{$base_path}.svg";
                    Storage::put($path, file_get_contents($file));
                } else {
                    $path = "{$base_path}.webp";
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($file)->toWebp();
                    Storage::put($path, $image, 'public');
                }

                $old_images = Storage::files("/images/{$this->table_name}/{$section_name}/{$field_key}");
                foreach ($old_images as $old_image) {
                    if (!str_contains($old_image, $hash_filename)) {
                        Storage::delete($old_image);
                    }
                }

                $saved_images[] = $path;

                $this->saveValue($key, $path, 'image');
            } else {
                $bad_imgs[] = $file;
            }
        }

        if (!empty($bad_imgs)) {
            info('Invalid image files detected', $bad_imgs);
        }

        return $saved_images;
    }

    private function mergeValuesWithDataRows()
    {
        foreach ($this->data_rows as &$section) {
            $section_name = $section['name'];

            foreach ($section['fields'] as &$field) {
                $dot_key = "{$section_name}.{$field['key']}";

                $field['dot_key'] = $dot_key;
                $field['value'] = $this->values[$dot_key] ?? null;
            }
        }
    }

    private function checkRedundantKeys($conf_keys)
    {
        $db_keys = DB::table($this->db_general_table_name)->get()->pluck('key')->toArray();
        $redundant_keys = array_diff($db_keys, $conf_keys);
        $this->deleteRecordByKey($redundant_keys);
    }

    private function deleteRecordByKey($keys)
    {
        foreach ($keys as $key) {
            DB::table($this->db_general_table_name)->where('key', $key)->delete();
        }
    }

    private function getValues($keys)
    {
        // NOTE:: Using this is wrong as hell
        // $this->checkRedundantKeys($keys);
        $results = [];
        foreach ($keys as $key) {
            start:
            $record = DB::table('configuration')->where('key', $key)->first();

            if ($record) {
                $final_type = $this->getFinalType($record);

                if ($final_type == 'image') {
                    $image = $record->image;
                    if (empty($image)) {
                        $results[$key] = null;
                    } else {
                        $image_obj = new \stdClass();
                        $image_obj->url = Storage::url($image);
                        $results[$key] = [$image_obj];
                    }
                } else {
                    $results[$key] = $final_type ? $record->$final_type : null;
                }
            } else {
                $new_rec = $this->writeKeyInTable($key);
                if ($new_rec) {
                    goto start;
                } else {
                    dd('failed to create new key');
                    return null;
                }
            }
        }
        $this->values = $results;
    }

    private function checkForTypeInconsistency($final_type)
    {
        if (array_key_exists($final_type, $this->corresponding_types)) {
            return $this->corresponding_types[$final_type];
        } else {
            return $final_type;
        }
    }

    private function writeKeyInTable($key)
    {
        $exists = DB::table($this->db_general_table_name)->where('key', $key)->exists();
        if (!$exists) {
            return DB::table($this->db_general_table_name)->insertGetId(['key' => $key]);
        }

        return null;
    }
}
