<?php

declare(strict_types=1);

namespace Mgcodeur\LaravelTranslationLoader\Translations;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

abstract class TranslationMigration
{
    /**
     * Implement by child files
     */
    abstract public function up(): void;

    abstract public function down(): void;

    /**
     * Insert if missing. If the language code doesn't exist, it will be created (enabled=true).
     */
    protected function add(string $locale, string $key, ?string $value): void
    {
        $languageId = $this->resolveLanguageId($locale);

        DB::table('translations')->updateOrInsert(
            ['language_id' => $languageId, 'key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Update only if the translation row exists; no-op otherwise.
     */
    protected function update(string $locale, string $key, ?string $value): void
    {
        $languageId = $this->findLanguageId($locale);
        if ($languageId === null) {
            return;
        }

        DB::table('translations')
            ->where('language_id', $languageId)
            ->where('key', $key)
            ->update(['value' => $value]);
    }

    /**
     * Delete translation row (if present).
     */
    protected function delete(string $locale, string $key): void
    {
        $languageId = $this->findLanguageId($locale);
        if ($languageId === null) {
            return;
        }

        DB::table('translations')
            ->where('language_id', $languageId)
            ->where('key', $key)
            ->delete();
    }

    /**
     * Utility: run multiple ops atomically.
     *
     * $ops = [
     *   ['add', 'en', 'welcome', 'Welcome'],
     *   ['update', 'fr', 'welcome', 'Bienvenue'],
     *   ['delete', 'en', 'old.key'],
     * ]
     */
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

    protected function resolveLanguageId(string $locale): int
    {
        $id = $this->findLanguageId($locale);
        if ($id !== null) {
            return $id;
        }

        return (int) DB::table('languages')->insertGetId([
            'code' => $locale,
            'name' => strtoupper($locale),
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
