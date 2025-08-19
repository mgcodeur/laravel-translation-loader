<?php

declare(strict_types=1);

namespace Mgcodeur\LaravelTranslationLoader\Translations;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\FileLoader;

final class DatabaseTranslationLoader implements Loader
{
    private FileLoader $fileLoader;

    private const CACHE_PREFIX = 'ltl_translations_list_';

    /**
     * @var array<string, array<string, string|null>> Runtime cache: locale => [key => value|null]
     */
    private static array $runtimeCache = [];

    public function __construct(Filesystem $filesystem, string $path)
    {
        $this->fileLoader = new FileLoader($filesystem, $path);
    }

    /**
     * @param  string  $locale
     * @param  string  $group
     * @param  string|null  $namespace
     * @return array<string, string|null>
     */
    public function load($locale, $group, $namespace = null): array
    {
        if ($group !== '*') {
            /** @var array<string, string|null> */
            return $this->fileLoader->load($locale, $group, $namespace);
        }

        $translations = $this->getTranslationsFromDb((string) $locale);

        $missingKeys = $this->getEmptyTranslationKeys($translations);
        if ($missingKeys === []) {
            return $translations;
        }

        /** @var string|null $fallbackLocale */
        $fallbackLocale = app()->getFallbackLocale();

        if ($fallbackLocale !== null && $fallbackLocale !== $locale) {
            $fallbackDb = $this->getTranslationsByKeys($missingKeys, $fallbackLocale);
            $this->fillFromSource($translations, $fallbackDb, $missingKeys);

            if ($missingKeys === []) {
                return $translations;
            }
        }

        $fallbackFile = $this->getTranslationsFromFile($missingKeys, (string) ($fallbackLocale ?? $locale), $namespace);
        $this->fillFromSource($translations, $fallbackFile, $missingKeys);

        return $translations;
    }

    /** @param string $namespace @param string $hint */
    public function addNamespace($namespace, $hint): void
    {
        $this->fileLoader->addNamespace((string) $namespace, (string) $hint);
    }

    /** @param string $path */
    public function addJsonPath($path): void
    {
        $this->fileLoader->addJsonPath((string) $path);
    }

    /**
     * @return array<string, string> namespace => hint
     */
    public function namespaces(): array
    {
        return $this->fileLoader->namespaces();
    }

    /**
     * @return array<string, string|null> key => value|null
     */
    private function getTranslationsFromDb(string $locale): array
    {
        if (isset(self::$runtimeCache[$locale])) {
            return self::$runtimeCache[$locale];
        }

        /** @var array<string, string|null> */
        return self::$runtimeCache[$locale] = Cache::rememberForever(
            self::CACHE_PREFIX.$locale,
            function () use ($locale): array {
                /** @var object{id:int}|null $language */
                $language = DB::table('languages')
                    ->where('code', $locale)
                    ->where('is_enabled', true)
                    ->first();

                if ($language === null) {
                    return [];
                }

                /** @var array<string, string|null> */
                return DB::table('translations')
                    ->where('language_id', $language->id)
                    ->pluck('value', 'key')
                    ->toArray();
            }
        );
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, string|null>
     */
    private function getTranslationsByKeys(array $keys, string $locale): array
    {
        if ($keys === []) {
            return [];
        }

        /** @var object{id:int}|null $language */
        $language = DB::table('languages')
            ->where('code', $locale)
            ->where('is_enabled', true)
            ->first();

        if ($language === null) {
            return [];
        }

        /** @var array<string, string|null> */
        return DB::table('translations')
            ->where('language_id', $language->id)
            ->whereIn('key', $keys)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, string|null>
     */
    private function getTranslationsFromFile(array $keys, string $locale, ?string $namespace): array
    {
        if ($keys === []) {
            return [];
        }

        /** @var array<string, string|null> $all */
        $all = $this->fileLoader->load($locale, '*', $namespace);

        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                /** @var string|null $value */
                $value = $all[$key];
                $result[$key] = $value;
            }
        }

        /** @var array<string, string|null> */
        return $result;
    }

    /**
     * @param  array<string, string|null>  $translations
     * @return list<string>
     */
    private function getEmptyTranslationKeys(array $translations): array
    {
        $emptyKeys = [];

        foreach ($translations as $translationKey => $translationValue) {
            if ($translationValue === '' || $translationValue === null) {
                $emptyKeys[] = (string) $translationKey;
            }
        }

        return $emptyKeys;
    }

    /**
     * @param  array<string, string|null>  $translations  (by ref)
     * @param  array<string, string|null>  $source
     * @param  list<string>  $missingKeys  (by ref)
     */
    private function fillFromSource(array &$translations, array $source, array &$missingKeys): void
    {
        if ($missingKeys === [] || $source === []) {
            return;
        }

        $stillMissing = array_flip($missingKeys);

        foreach ($source as $key => $value) {
            if (isset($stillMissing[$key]) && $value !== '' && $value !== null) {
                $translations[$key] = $value;
                unset($stillMissing[$key]);
            }
        }

        /** @var list<string> */
        $missingKeys = array_keys($stillMissing);
    }
}
