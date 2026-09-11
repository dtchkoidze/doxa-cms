<?php

/**
 * Изолированный словарь Doxa (не пересекается со словарём приложения-хоста).
 * Готовые JSON отдаются статикой из public хоста: /doxa/dictionary/{lang}.json
 */
return [

    /**
     * JSON со списком ключей после скана Vue (внутри пакета Core).
     */
    'keys_path' => dirname(__DIR__) . '/dictionary/vue-keys.json',

    /**
     * Каталог готовых {lang}.json относительно base_path() хоста.
     * Браузер: /doxa/dictionary/en.json — не /dictionary/ хоста.
     */
    'dist_path' => 'public/doxa/dictionary',

    /**
     * Каталог {lang}_not_found.js (рядом с vue-keys, не словарь хоста).
     */
    'not_found_path' => dirname(__DIR__) . '/dictionary',

    /**
     * Каталоги для скана vocab("vcb.*") / vocab('txt.*').
     * Пока только auth Vue (User apps); админку добавим позже.
     */
    'scan_paths' => [
        dirname(__DIR__, 2) . '/User/src/Resources/assets/js/apps',
    ],
];
