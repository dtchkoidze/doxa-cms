<?php

namespace Doxa\Core\Console\Commands;

use Doxa\Core\Helpers\Dictionary;
use Doxa\Core\Libraries\Logging\Clog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Собирает JSON словаря Doxa по vue-keys.json (через Vocabulary в БД).
 */
class DictionaryBuildCommand extends Command
{
    protected $signature = 'doxa:dictionary-build';

    protected $description = 'Build Doxa dictionary JSON into host public/doxa/dictionary';

    /**
     * Читает ключи, наполняет из БД, пишет {lang}.json и {lang}_not_found.js.
     * Возвращает код выхода команды.
     */
    public function handle(): int
    {
        $keysPath = (string) config('doxa.dictionary.keys_path');
        if (!File::exists($keysPath)) {
            $this->error('Keys file not found. Run doxa:dictionary-scan first: ' . $keysPath);

            return self::FAILURE;
        }

        $keys = json_decode(File::get($keysPath), true);
        if (!is_array($keys)) {
            $this->error('Invalid JSON: ' . $keysPath);

            return self::FAILURE;
        }

        // Пустой скан — всё равно пишем пустые файлы по локалям из БД
        if (empty($keys['vocabulary']) && empty($keys['text_blocks'])) {
            $keys = [
                'vocabulary' => [],
                'text_blocks' => [],
            ];
        }

        [$dictionary, $missing] = Dictionary::create($keys);
        Clog::write('missing_keys', 'doxa $missing', $missing);

        $distRelative = (string) config('doxa.dictionary.dist_path');
        $distPath = base_path($distRelative);
        File::ensureDirectoryExists($distPath);

        // not_found — рядом с vue-keys в пакете Core (не resources/js/dictionary хоста)
        $notFoundDir = (string) config(
            'doxa.dictionary.not_found_path',
            dirname($keysPath)
        );
        File::ensureDirectoryExists($notFoundDir);

        // Даже при пустых ключах — файл на каждую локаль из БД
        if ($dictionary === []) {
            $localeCodes = \Illuminate\Support\Facades\DB::table('locales')->pluck('code')->all();
            if ($localeCodes === []) {
                $localeCodes = ['en'];
            }
            foreach ($localeCodes as $lang) {
                $dictionary[$lang] = [
                    'vocabulary' => new \stdClass(),
                    'text_blocks' => new \stdClass(),
                ];
            }
        }

        foreach ($dictionary as $lang => $data) {
            $out = $distPath . DIRECTORY_SEPARATOR . $lang . '.json';
            File::put($out, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
            $this->info('Generated: ' . $out);

            $missingPath = $notFoundDir . DIRECTORY_SEPARATOR . $lang . '_not_found.js';
            $missingKeys = [];
            if (!empty($missing[$lang]) && is_array($missing[$lang])) {
                $missingKeys = $this->formatMissingKeys($missing[$lang]);
            }

            // Нет missing — убрать старый not_found (иначе ключ остаётся после появления перевода)
            if ($missingKeys === []) {
                if (File::exists($missingPath)) {
                    File::delete($missingPath);
                    $this->info('Removed obsolete: ' . $missingPath);
                }
                continue;
            }

            $jsContent = 'export default ' . json_encode($missingKeys, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . ";\n";
            File::put($missingPath, $jsContent);
            $this->info('Generated missing keys: ' . $missingPath);
        }

        $this->info('Doxa dictionary build done → ' . $distPath);

        return self::SUCCESS;
    }

    /**
     * Собирает плоский список отсутствующих ключей с префиксами vcb./txt. (как в Eventer).
     *
     * @param  array{vocabulary?: list<string>, text_blocks?: list<string>}  $missing
     * @return list<string>
     */
    private function formatMissingKeys(array $missing): array
    {
        $result = [];

        if (!empty($missing['vocabulary']) && is_array($missing['vocabulary'])) {
            foreach ($missing['vocabulary'] as $key) {
                $result[] = 'vcb.' . $key;
            }
        }

        if (!empty($missing['text_blocks']) && is_array($missing['text_blocks'])) {
            foreach ($missing['text_blocks'] as $key) {
                $result[] = 'txt.' . $key;
            }
        }

        sort($result);

        return $result;
    }
}
