<?php

namespace Mgcodeur\LaravelTranslationLoader\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GenerateTranslationsCommand extends Command
{
    protected $signature = 'translation:generate {--locale=* : Filter by locale (optional)}';

    protected $description = 'Génère les fichiers de traduction JSON pour un SPA à partir de la base de données';

    public function handle(): int
    {
        $locales = $this->option('locale') ?: $this->getEnabledLocales();
        $outputPath = Config::get('translation-loader.build.output_path');

        if (! File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        foreach ($locales as $locale) {
            $translations = $this->getTranslations($locale);

            if (empty($translations)) {
                $this->warn("Aucune traduction pour [$locale], fichier ignoré.");

                continue;
            }

            $file = "{$outputPath}/{$locale}.json";
            File::put($file, $this->encodeJson($this->undot($translations), 2));

            $this->info("✅ Fichier généré: {$file}");
        }

        return self::SUCCESS;
    }

    private function getEnabledLocales(): array
    {
        return DB::table('languages')
            ->where('is_enabled', true)
            ->pluck('code')
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    private function getTranslations(string $locale): array
    {
        $language = DB::table('languages')
            ->where('code', $locale)
            ->where('is_enabled', true)
            ->first();

        if (! $language) {
            return [];
        }

        return DB::table('translations')
            ->where('language_id', $language->id)
            ->whereNotNull('value')
            ->pluck('value', 'key')
            ->toArray();
    }

    private function undot(array $dotArray): array
    {
        $result = [];

        foreach ($dotArray as $key => $value) {
            $segments = explode('.', $key);
            $temp = &$result;

            foreach ($segments as $segment) {
                if (! array_key_exists($segment, $temp) || ! is_array($temp[$segment])) {
                    $temp[$segment] = [];
                }
                $temp = &$temp[$segment];
            }

            $temp = $value;
        }

        return $result;
    }

    private function encodeJson(array $data, int $spaces = 2): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return preg_replace_callback('/^( +)/m', function ($m) use ($spaces) {
            $count = (int) floor(strlen($m[1]) / 4);

            return str_repeat(' ', $count * $spaces);
        }, $json);
    }
}
