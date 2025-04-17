<?php

namespace Doxa\Core\Libraries\ModuleManager;

use Closure;
use Illuminate\Support\Str;
use Doxa\Core\Libraries\Chlo;
use Illuminate\Validation\Rule;
use Doxa\Core\Helpers\Logging\Clog;
use Illuminate\Support\Facades\Validator;

trait ModuleValidator
{
    protected $validation_rules;

    protected $validator;

    protected function makeValidation()
    {
        $this->validation_rules = [];

        $this->requestCorrection();
        $this->prepareRequest();

        $this->fields = $this->package->getEditingFields();
        $this->variationFields = $this->package->getEditingVariationFields();

        $this->buildValidationRules();
        //dump($this->validation_rules);

        $this->validator = Validator::make(
            $this->data,
            $this->validation_rules,
            $this->package->hasVariations() ? $this->getVariationValidationMessages() : []
        );

        if ($this->validator->fails()) {
            $this->package->hasVariations() && $this->getVariationsValidationAlerts();
        }

        $this->validator;
    }

    protected function buildValidationRules()
    {
        $data = [];
        $data['base'] = $this->fields;
        !empty($this->variationFields) && $data['variation'] = $this->variationFields;

        $this->variation_url_keys = [];

        foreach ($data as $type => $fields) {
            //dump('$fields: ', $fields);
            foreach ($fields as $key => $field) {
                //dump(['$key' => $key, '$field' => $field]);

                //dump('$field: '. $field->key);

                $method = 'buildValidationRulesFor__' . $key;

                //dump('$method: ', $method);

                if ($type == 'base') {
                    if (method_exists($this, $method)) {
                        $this->$method($field, $type);
                    } else {
                        if (!empty($field->validation_rules)) {
                            foreach ($field->validation_rules as $rule) {
                                $this->getCustomValidationRule($field, $rule, $type) ?:
                                    $this->getValidationRule($key, $rule, $type, $field);
                            }
                        }
                    }
                }

                if ($type == 'variation') {
                    foreach (Chlo::altAsAssocById() as $channel_id => $channel) {
                        foreach ($channel->locales as $locale_id => $locale) {
                            $dot = 'variation.' . $channel_id . '.' . $locale_id . '.' . $field->key;
                            if (method_exists($this, $method)) {
                                $this->$method($field, $type, $channel_id, $locale_id);
                            } else {
                                if (!empty($field->validation_rules)) {
                                    //dump($field->validation_rules);
                                    foreach ($field->validation_rules as $rule) {
                                        $this->getCustomValidationRule($field, $rule, $type, $channel_id, $locale_id) ?:
                                            $this->getValidationRule($dot, $rule, $type, $field);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    protected function getCustomValidationRule($field, $rule, $type, $channel_id = false, $locale_id = false)
    {
        $map = explode('::', $rule);
        $method = $map[0];
        if (method_exists($this, $method)) {
            return $this->$method($field, $map[1], $type, $channel_id, $locale_id);
        }
    }

    protected function getValidationRule($key, $rule, $type, $field)
    {
        $this->validation_rules[$key][] = $rule;
    }

    /**************************** Validation rules by field *******************************/

    protected function buildValidationRulesFor__key($field, $type, $channel_id = false, $locale_id = false)
    {

        if ($type == 'base') {
            $__key = $field->key;
            $this->validation_rules[$field->key][] = $this->id > 0 ? Rule::unique($this->table)->ignore($this->id) : Rule::unique($this->table);
            if ($this->package->isEditingFieldExists($field->key, 'variation')) {
                $this->validation_rules[$field->key][] = Rule::unique($this->variations_table, $field->key);
            }
        }

        if ($type == 'variation') {
            $__key = $dot = 'variation.' . $channel_id . '.' . $locale_id . '.' . $field->key;
            $val = $this->data['variation'][$channel_id][$locale_id][$field->key];
            if ($val) {
                if ($this->package->isEditingFieldExists($field->key, 'base')) {
                    $this->validation_rules[$dot][] = Rule::unique($this->table, $field->key);
                    $this->validation_rules[$dot][] = Rule::notIn([$this->data[$field->key]]);
                }
                $this->validation_rules[$dot][] = function (string $attribute, mixed $value, Closure $fail) use ($field, $channel_id, $locale_id) {
                    ($value && $this->isUniqueVariationValue($field, $value, $channel_id, $locale_id)) && $fail("The key {$value} has already been taken");
                };
            }
        }
        if (!empty($field->validation_rules)) {
            foreach ($field->validation_rules as $rule) {
                $this->getValidationRule($__key, $rule, $type, $field);
            }
        }
    }

    protected function buildValidationRulesFor__id($field, $type, $channel_id = false, $locale_id = false)
    {
        if ($type == 'base') {
            //$__key = $field->key;
            $this->validation_rules[$field->key][] = $this->id > 0 ? Rule::unique($this->table)->ignore($this->id) : Rule::unique($this->table);
        }
    }

    protected function buildValidationRulesFor__url_key($field, $type, $channel_id = false, $locale_id = false)
    {
        $this->buildValidationRulesFor__key($field, $type, $channel_id, $locale_id);
    }

    /***************************** Custom Validation rules ********************************/

    protected function requiredIf($field, $params, $type, $channel_id, $locale_id)
    {
        $a = explode(',', $params);
        if (sizeof($a) < 2) {
            return false;
        }
        if ($type == 'variation') {
            $dot = 'variation.' . $channel_id . '.' . $locale_id . '.' . $field->key;
            $this->validation_rules[$dot][] = Rule::requiredIf(
                isset($this->data['variation'][$channel_id][$locale_id][$a[0]])
                    &&
                    $this->data['variation'][$channel_id][$locale_id][$a[0]] == $a[1]
            );
            return true;
        }
    }

    /***************************** Preparing request ********************************/

    protected function requestCorrection()
    {
        foreach ($this->data as $key => &$value) {
            if ($key == 'url_key') {
                $value = strtolower(trim($value));
            } else {
                if ($key == 'variation') {
                    foreach ($value  as $channel_id => &$locales) {
                        foreach ($locales  as $locale_id => &$locale) {
                            foreach ($locale  as $_key => &$_value) {
                                if ($key == 'url_key') {
                                    $_value = strtolower(trim($_value));
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    protected function prepareRequest() {}

    /***************************** Specific helpers ********************************/

    /**
     * Check if variation value is unique
     *
     * @param $field
     * @param string $value
     * @param int $_channel_id
     * @param int $_locale_id
     * @return boolean
     */
    protected function isUniqueVariationValue($field, $value, $_channel_id, $_locale_id): bool
    {
        $channels = Chlo::asAssocById()['channels'];
        foreach ($channels as $channel_id => $channel) {
            foreach ($channel->locales as $locale_id => $locale) {
                $_value = isset($this->data['variation'][$channel_id][$locale_id][$field->key]) ?
                    $this->data['variation'][$channel_id][$locale_id][$field->key]
                    :
                    null;
                if ($_value == $value && $_channel_id != $channel_id && $_locale_id != $locale_id) {
                    return true;
                }
            }
        }
        return false;
    }

    /************************************** Adds ******************************************/

    protected function getVariationValidationMessages()
    {
        return [
            'variation.*.*.*.required' => 'Required if active',
            'variation.*.*.*.max' => 'Max limit reached',
            'variation.*.*.*.unique' => 'The url key has already been taken',
            'variation.*.*.url_key.not_in' => 'The url key has already been taken',
            //'variation.*.*.*.url_key_validation' => 'The url key is not valid.',
            'variation.*.*.*.regex' => 'The url key is not valid.',
        ];
    }

    protected function getVariationsValidationAlerts()
    {
        $alerts = [];
        foreach ($this->validator->errors()->messages() as $key => $value) {
            $a = explode('.', $key);
            if (sizeof($a) == 4) {
                if (!in_array($a[1] . '_' . $a[2], $alerts)) {
                    $alerts[] = $a[1] . '_' . $a[2];
                    $locale = Chlo::getLocaleCodeById($a[2]);
                    $str = omniModuleTrans('default', 'variation_alert');

                    //$str = '? ენისთვის ?';
                    //$go = 'გადასვლა';
                    $link = '<span @click="applyChanelAndLocale(' . $a[1] . ',`' . $a[2] . '`)" class="" channel_id="' . $a[1] . '" locale_id="' . $a[2] . '">' . strtoupper($locale) . '</span>';
                    //$link = '<span @click="applyChanelAndLocale('.$a[1].',`'.$a[2].'`)" class="underline cursor-pointer set_channel_ws_locale" channel_id="' . $a[1] . '" locale_id="' . $a[2] . '">'.ucfirst(omniModuleTrans('default','variation_error')).'</span>';
                    $this->validator->errors()->add(
                        'variation_alert',
                        [
                            'key' => $a[1] . '_' . $a[2],
                            'locale' => [
                                'id' => $a[2],
                            ],
                            'channel' => [
                                'id' => $a[1],
                            ],
                            //'message' => Str::replaceArray('?', [$link, strtoupper($locale)], $str)
                            'message' => $link,
                            'text' => strtoupper($locale)
                        ]

                    );
                }
            }
        };
    }
}
