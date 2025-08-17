<?php

declare(strict_types=1);

namespace Mgcodeur\LaravelTranslationLoader\Translations;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Translation\FileLoader;
use Mgcodeur\LaravelTranslationLoader\Models\Language;

final class DatabaseTranslationLoader implements Loader
{
    private FileLoader $fileLoader;

    private const CACHE_PREFIX = 'laravel_translation_loader_language_';

    public function __construct(Filesystem $filesystem, string $path)
    {
        $this->fileLoader = new FileLoader($filesystem, $path);
    }

    public function load($locale, $group, $namespace = null): array
    {
        if ($group !== '*') {
            return $this->fileLoader->load($locale, $group, $namespace);
        }

        $translations = $this->getTranslationsFromDb($locale);
        $fallbackLocale = config('app.fallback_locale');

        if (! $fallbackLocale) {
            return $translations;
        }

        if (empty($translations)) {
            foreach (
                [
                    $this->getTranslationsFromDb($fallbackLocale),
                    $this->fileLoader->load($fallbackLocale, '*', $namespace),
                ] as $fallbackSource
            ) {
                $translations = $this->fillMissingTranslations($translations, $fallbackSource);
            }

            return $translations;
        }

        $hasEmptyTranslations = array_filter(
            $translations,
            fn ($translationValue) => self::isEmptyValue($translationValue)
        );

        if ($hasEmptyTranslations) {
            // TODO: ne charger que ce que l'on a vraiment besoin
            $translations = $this->fillMissingTranslations($translations, $this->getTranslationsFromDb($fallbackLocale));
            $translations = $this->fillMissingTranslations($translations, $this->fileLoader->load($fallbackLocale, '*', $namespace));
        }

        return $translations;
    }

    public function addNamespace($namespace, $hint): void
    {
        $this->fileLoader->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path): void
    {
        $this->fileLoader->addJsonPath($path);
    }

    public function namespaces(): array
    {
        return $this->fileLoader->namespaces();
    }

    private function getTranslationsFromDb(string $locale): array
    {
        return Cache::rememberForever(self::CACHE_PREFIX.$locale, function () use ($locale) {
            $language = Language::where('code', $locale)
                ->where('is_enabled', true)
                ->first();

            if (! $language) {
                return [];
            }

            return $language->translations()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    private function fillMissingTranslations(array $currentTranslations, array $fallbackTranslations): array
    {
        $validFallbackTranslations = array_filter(
            $fallbackTranslations,
            fn ($translationValue) => self::isFilledValue($translationValue)
        );

        if (empty($validFallbackTranslations)) {
            return $currentTranslations;
        }

        $emptyKeys = array_keys(array_filter(
            $currentTranslations,
            fn ($translationValue) => self::isEmptyValue($translationValue)
        ));

        $missingKeys = array_diff(
            array_keys($validFallbackTranslations),
            array_keys($currentTranslations)
        );

        $keysNeedingFallback = $emptyKeys === []
            ? $missingKeys
            : array_unique(array_merge($emptyKeys, $missingKeys));

        if (empty($keysNeedingFallback)) {
            return $currentTranslations;
        }

        $fallbackSubset = array_intersect_key(
            $validFallbackTranslations,
            array_flip($keysNeedingFallback)
        );

        return array_replace($currentTranslations, $fallbackSubset);
    }

    private static function isEmptyValue($translationValue): bool
    {
        return $translationValue === null || trim((string) $translationValue) === '';
    }

    private static function isFilledValue($translationValue): bool
    {
        return ! self::isEmptyValue($translationValue);
    }
}
