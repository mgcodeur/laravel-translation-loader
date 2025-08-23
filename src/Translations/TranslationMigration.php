<?php

declare(strict_types=1);

namespace Mgcodeur\LaravelTranslationLoader\Translations;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Mgcodeur\LaravelTranslationLoader\Models\Translation;

abstract class TranslationMigration
{
    /**
     * Implement by child files
     */
    abstract public function up(): void;

    abstract public function down(): void;

    protected function add(string $locale, string $key, ?string $value): void
    {
        $languageId = $this->findLanguageId($locale);
        if (! $languageId) {
            return;
        }

        Translation::updateOrCreate(
            ['language_id' => $languageId, 'key' => $key],
            ['value' => $value]
        );
    }

    protected function addMany(string|array $localeOrPayload, ?array $keyValues = null): void
    {
        DB::transaction(function () use ($localeOrPayload, $keyValues) {
            if (is_string($localeOrPayload)) {
                $this->addManyForLocale($localeOrPayload, $keyValues ?? []);

                return;
            }

            foreach ($localeOrPayload as $locale => $pairs) {
                $this->addManyForLocale((string) $locale, (array) $pairs);
            }
        });
    }

    protected function addManyForLocale(string $locale, array $keyValues): void
    {
        $languageId = $this->findLanguageId($locale);
        if (! $languageId) {
            return;
        }

        foreach ($keyValues as $key => $value) {
            Translation::updateOrCreate(
                ['language_id' => $languageId, 'key' => $key],
                ['value' => $value]
            );
        }
    }

    protected function update(string $locale, string $key, ?string $value): void
    {
        $languageId = $this->findLanguageId($locale);
        if (! $languageId) {
            return;
        }

        $translation = Translation::query()
            ->where('language_id', $languageId)
            ->where('key', $key)->first();

        if ($translation) {
            $translation->value = $value;
            $translation->save();
        }
    }

    protected function delete(string $locale, string $key): void
    {
        $languageId = $this->findLanguageId($locale);
        if (! $languageId) {
            return;
        }

        $translation = Translation::query()
            ->where('language_id', $languageId)
            ->where('key', $key)->first();

        if ($translation) {
            $translation->delete();
        }
    }

    protected function deleteAll(string $key): void
    {
        Translation::query()
            ->where('key', $key)
            ->delete();
    }

    protected function transaction(array $ops, bool $stopOnError = true): void
    {
        DB::transaction(function () use ($ops, $stopOnError) {
            foreach ($ops as $op) {
                $method = Arr::get($op, 0);
                $args = array_values(array_slice($op, 1));
                if (! method_exists($this, (string) $method)) {
                    if ($stopOnError) {
                        throw new \RuntimeException("Unknown op: {$method}");
                    }

                    continue;
                }
                $this->{$method}(...$args);
            }
        });
    }

    protected function findLanguageId(string $locale): ?int
    {
        $row = DB::table('languages')
            ->where('code', $locale)
            ->where('is_enabled', true)
            ->first(['id']);

        return $row->id ?? null;
    }
}
