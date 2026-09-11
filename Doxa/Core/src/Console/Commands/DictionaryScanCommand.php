<?php

namespace Doxa\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Сканирует Vue/JS Doxa на ключи vocab и пишет vue-keys.json.
 */
class DictionaryScanCommand extends Command
{
    protected $signature = 'doxa:dictionary-scan';

    protected $description = 'Scan Doxa Vue/JS for vocab keys into Core dictionary/vue-keys.json';

    /**
     * Сканирует scan_paths и сохраняет список ключей.
     * Возвращает код выхода команды.
     */
    public function handle(): int
    {
        $paths = config('doxa.dictionary.scan_paths', []);
        if (!is_array($paths) || $paths === []) {
            $this->error('doxa.dictionary.scan_paths is empty');

            return self::FAILURE;
        }

        $raw = [
            'vcb' => [],
            'txt' => [],
        ];
        $regex = "/['\"`]((vcb|txt)\\.[\\w.-]+)['\"`]/";

        foreach ($paths as $path) {
            if (!is_string($path) || $path === '' || !is_dir($path)) {
                $this->warn('Skip missing scan path: ' . (string) $path);
                continue;
            }
            $this->scanDirectory($path, $raw, $regex);
            $this->info('Scanned: ' . $path);
        }

        $result = [
            'vocabulary' => array_values(array_unique($raw['vcb'])),
            'text_blocks' => array_values(array_unique($raw['txt'])),
        ];
        sort($result['vocabulary']);
        sort($result['text_blocks']);

        $keysPath = (string) config('doxa.dictionary.keys_path');
        File::ensureDirectoryExists(dirname($keysPath));
        File::put(
            $keysPath,
            json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n"
        );

        $this->info('Wrote: ' . $keysPath);
        $this->info('vocabulary: ' . count($result['vocabulary']) . ', text_blocks: ' . count($result['text_blocks']));

        return self::SUCCESS;
    }

    /**
     * Рекурсивно собирает ключи из .vue/.js/.ts в каталоге.
     *
     * @param  array{vcb: list<string>, txt: list<string>}  $raw
     */
    private function scanDirectory(string $dir, array &$raw, string $regex): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            if (!preg_match('/\.(vue|js|ts)$/', $name)) {
                continue;
            }
            // Как в Eventer extractor: не сканировать списки missing
            if (preg_match('/_not_found\.js$/', $name)) {
                continue;
            }

            $content = File::get($file->getPathname());
            if (!preg_match_all($regex, $content, $matches)) {
                continue;
            }

            foreach ($matches[1] as $fullKey) {
                $dot = strpos($fullKey, '.');
                if ($dot === false) {
                    continue;
                }
                $ns = substr($fullKey, 0, $dot);
                $key = substr($fullKey, $dot + 1);
                if ($ns === 'vcb' || $ns === 'txt') {
                    $raw[$ns][] = $key;
                }
            }
        }
    }
}
