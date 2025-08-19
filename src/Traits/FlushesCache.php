<?php

declare(strict_types=1);

namespace Mgcodeur\LaravelTranslationLoader\Traits;

use Illuminate\Database\Eloquent\Model;
use Mgcodeur\LaravelTranslationLoader\Models\Language;
use Mgcodeur\LaravelTranslationLoader\Models\Translation;
use Mgcodeur\LaravelTranslationLoader\Translations\DatabaseTranslationLoader;

trait FlushesCache
{
    protected static function bootFlushesCache(): void
    {
        static::created(static function (Model $model): void {
            self::flushCacheForModel($model);
        });

        static::updated(static function (Model $model): void {
            self::flushCacheForModel($model);
        });

        static::deleted(static function (Model $model): void {
            self::flushCacheForModel($model);
        });
    }

    private static function flushCacheForModel(Model $model): void
    {
        if ($model instanceof Translation) {
            self::flushCacheForTranslation($model);

            return;
        }

        if ($model instanceof Language) {
            self::flushCacheForLanguage($model);
        }
    }

    private static function flushCacheForTranslation(Translation $translation): void
    {
        $locale = $translation->language()->value('code');
        if (is_string($locale) && $locale !== '') {
            DatabaseTranslationLoader::clearLocaleCache($locale);
        }
    }

    private static function flushCacheForLanguage(Language $language): void
    {
        $locale = (string) ($language->code ?? '');
        if ($locale !== '') {
            DatabaseTranslationLoader::clearLocaleCache($locale);
        }
    }
}
