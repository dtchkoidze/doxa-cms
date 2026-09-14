# Словарь Doxa (изолированный)

Отдельный пайплайн от словаря приложения-хоста (Eventer и т.п.).  
Не использует `/dictionary/...` хоста, `vite.dictionary-extractor.js` хоста и его `useDictionary`.

## Зачем

Тексты auth (и позже admin) в пакетах Doxa переводятся своим словарём:

- ключи в Vue: `vocab("vcb....")` / `vocab("txt....")`
- фронт: `Doxa/Core/src/Resources/assets/js/dictionary/useDictionary.js`
- отдача: **статика** `{host}/public/doxa/dictionary/{lang}.json` → URL `/doxa/dictionary/{lang}.json`

## Где что лежит

| Что | Путь |
|-----|------|
| Конфиг | `Doxa/Core/config/dictionary.php` → `config('doxa.dictionary')` |
| Скан путей | `doxa.dictionary.scan_paths` (сейчас: User auth Vue `.../User/.../js/apps`) |
| Список ключей после скана | `Doxa/Core/dictionary/vue-keys.json` |
| Готовые JSON | `{хост}/public/doxa/dictionary/{lang}.json` (`dist_path` = `public/doxa/dictionary` от `base_path()`) |
| Missing keys (not_found) | `Doxa/Core/dictionary/{lang}_not_found.js` (`not_found_path`) |
| JS `loadDictionary` / `vocab` | `Doxa/Core/.../dictionary/useDictionary.js` (alias `@doxa-dict` в User Vite) |

Команды регистрирует `Doxa\Core\Providers\CoreServiceProvider` (должен быть в `bootstrap/providers.php` хоста).

## Как запускать

Команды выполняются **из корня приложения-хоста** (там, где `artisan`), не из репозитория `doxa-cms.loc` отдельно — нужен Laravel + БД хоста (переводы берутся из Vocabulary).

### 1. Скан ключей

Обходит `.vue` / `.js` / `.ts` в `scan_paths`, ищет строки вида `vcb.*` и `txt.*`, пишет `vue-keys.json`.

```bash
php artisan doxa:dictionary-scan
```

### 2. Сборка JSON для браузера

Читает `vue-keys.json`, наполняет тексты из БД (`Dictionary::create`), пишет:

- `{host}/public/doxa/dictionary/{lang}.json` — для браузера;
- `Doxa/Core/dictionary/{lang}_not_found.js` — ключи без перевода в БД (формат как у Eventer: `export default ["vcb.…", …]`). Если missing пуст — старый файл удаляется.

Не пишет в `resources/js/dictionary` хоста.

```bash
php artisan doxa:dictionary-build
```

Обычный порядок после правок ключей во Vue:

```bash
php artisan doxa:dictionary-scan
php artisan doxa:dictionary-build
```

Каталог `public/doxa/dictionary` создаётся при build. Заранее пустые JSON класть не нужно.

## Фронт (auth User)

В `user.js` перед mount:

```js
import { loadDictionary } from "@doxa-dict/useDictionary.js";
const locale = document.documentElement.lang || "en";
loadDictionary(locale).finally(() => {
    app.mount("#auth-app");
});
```

`document.documentElement.lang` задаётся layout’ом (`app()->getLocale()`), локаль на auth меняется через `/doxa/locale/{code}` (cookie `_project_locale`).

### Нет файла (404)

Если `/doxa/dictionary/{lang}.json` отсутствует, `loadDictionary` **бросает** ошибку (`not found`), пустой словарь не подставляется. Нужно прогнать `doxa:dictionary-build`.

Нет ключа в загруженном JSON — `vocab` возвращает `[ключ]` (как плейсхолдер).

## Чего не делать

- Не писать в `public/dictionary` и `resources/js/dictionary` хоста.
- Не подмешивать словарь Doxa в словарь хоста и наоборот.
- Не путать URL: хост — `/dictionary/{lang}-{scope}.json`; Doxa — `/doxa/dictionary/{lang}.json`.

## Связь с хостом (Eventer)

```bash
php artisan dictionary:sync
php artisan dictionary:sync --skip-doxa
php artisan dictionary:sync --add-rows
php artisan dictionary:sync --add-rows --lng=en
```

`dictionary:sync` = `dictionary:scan` → `dictionary:build` (те же три опции).

По отдельности:

```bash
php artisan dictionary:scan          # хост vue-keys + doxa:dictionary-scan
php artisan dictionary:scan --skip-doxa

php artisan dictionary:build         # хост public/dictionary + not_found + doxa:dictionary-build
php artisan dictionary:build --skip-doxa
php artisan dictionary:build --add-rows
php artisan dictionary:build --add-rows --lng=en
```

`--add-rows` (у **build** / **sync**): после билдов хоста и Doxa перезаписывает `export/dictionary_rows.json` из `*_not_found.js` (хост + Doxa), значения `""`. Пишет файл всегда. Без `--lng` — все locale с not_found.

После заполнения текстов в `dictionary_rows.json`:

```bash
php artisan dictionary:import-rows --skip-existing --rebuild
```

`--rebuild` — после импорта в БД снова `dictionary:build` (JSON в `public`).

Vite больше не сканирует словарь.
