<?php

namespace Mgcodeur\LaravelTranslationLoader\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GenerateTranslationsCommand extends Command
{
    protected $signature = 'translation:generate {--locale=*} {--output-path=*}';

    protected $description = 'Generate JSON translation files for an SPA from the database';

    public function handle(): int
    {
        $locales = $this->option('locale') ?: $this->getEnabledLocales();
        $outputPathsOption = $this->option('output-path');

        $outputPaths = $outputPathsOption
            ? (is_array($outputPathsOption) ? $outputPathsOption : [$outputPathsOption])
            : (array) config('translation-loader.build.output_path', 'translations');

        foreach ($outputPaths as $outputPath) {
            $clean = trim($outputPath);

            $resolvedPath = str_starts_with($clean, '/')
                ? $clean
                : public_path($clean);

            if (! File::exists($resolvedPath)) {
                File::makeDirectory($resolvedPath, 0755, true);
            }
        }

        foreach ($locales as $locale) {
            $translations = $this->getTranslations($locale);

            if (empty($translations)) {
                continue;
            }

            foreach ($outputPaths as $outputPath) {
                $clean = trim($outputPath);

                $resolvedPath = str_starts_with($clean, '/')
                    ? $clean
                    : public_path($clean);

                $file = $resolvedPath.DIRECTORY_SEPARATOR."{$locale}.json";
                $json = $this->encodeJson($this->undot($translations), 2);

                File::put($file, $json);

                $this->info("Generated translation file: {$file}");
            }
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
                if (! isset($temp[$segment]) || ! is_array($temp[$segment])) {
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
