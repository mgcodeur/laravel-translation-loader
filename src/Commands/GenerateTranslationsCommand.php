<?php

namespace Mgcodeur\LaravelTranslationLoader\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class GenerateTranslationsCommand extends Command
{
    protected $signature = 'translation:generate {--locale=* : Filter by locale (optional)} {--output-path=* : Output path(s) for the generated files (optional, defaults to config value)}';

    protected $description = 'Generate JSON translation files for an SPA from the database';

    public function handle(): int
    {
        $locales = $this->option('locale') ?: $this->getEnabledLocales();

        $outputPathsOption = $this->option('output-path');
        $outputDisk = Config::get('translation-loader.build.output_disk', 'public');

        if ($outputPathsOption) {
            $outputPaths = is_array($outputPathsOption) ? $outputPathsOption : [$outputPathsOption];
            $usePublicPath = true;
        } else {
            $outputPaths = (array) Config::get('translation-loader.build.output_path');
            $usePublicPath = false;
        }

        $disk = Storage::disk($outputDisk);

        foreach ($outputPaths as $outputPath) {
            $resolvedPath = $usePublicPath
                ? public_path(trim($outputPath, '/'))
                : trim($outputPath, '/');

            if ($usePublicPath) {
                if (! File::exists($resolvedPath)) {
                    File::makeDirectory($resolvedPath, 0755, true);
                    $this->info("📁 Directory created: {$resolvedPath}");
                }
            } else {
                if (! $disk->exists($resolvedPath)) {
                    $disk->makeDirectory($resolvedPath);
                    $this->info("📁 Directory created on disk [{$outputDisk}]: {$resolvedPath}");
                }
            }
        }

        foreach ($locales as $locale) {
            $translations = $this->getTranslations($locale);

            if (empty($translations)) {
                $this->warn("⚠️  No translations found for [$locale], file skipped.");

                continue;
            }

            foreach ($outputPaths as $outputPath) {
                $resolvedPath = $usePublicPath
                    ? public_path(trim($outputPath, '/'))
                    : trim($outputPath, '/');

                $file = rtrim($resolvedPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR."{$locale}.json";
                $json = $this->encodeJson($this->undot($translations), 2);

                if ($usePublicPath) {
                    File::put($file, $json);
                } else {
                    $disk->put($file, $json);
                    $file = $disk->path($file);
                }

                $this->info("✅ File generated: {$file}");
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
        /** @var array<string, mixed> $result */
        $result = [];

        foreach ($dotArray as $key => $value) {
            $segments = explode('.', $key);

            /** @var array<string, mixed> $temp */
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
