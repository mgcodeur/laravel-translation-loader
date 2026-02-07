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

    protected array $groupStack = [];

    protected function add(string $locale, string $key, ?string $value): void
    {
        [$group, $key] = $this->resolveGroupAndKey($key);

        $languageId = $this->findLanguageId($locale);
        if (! $languageId) {
            return;
        }

        Translation::updateOrCreate(
            [
                'language_id' => $languageId,
                'group' => $group,
                'key' => $key,
            ],
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
            [$group, $resolvedKey] = $this->resolveGroupAndKey((string) $key);

            Translation::updateOrCreate(
                [
                    'language_id' => $languageId,
                    'group' => $group,
                    'key' => $resolvedKey,
                ],
                ['value' => $value]
            );
        }
    }

    protected function update(string $locale, string $key, ?string $value): void
    {
        [$group, $key] = $this->resolveGroupAndKey($key);

        $languageId = $this->findLanguageId($locale);
        if (! $languageId) {
            return;
        }

        $translation = Translation::query()
            ->where('language_id', $languageId)
            ->where('group', $group)
            ->where('key', $key)
            ->first();

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

    protected function deleteAll(string ...$keys): void
    {
        Translation::query()
            ->whereIn('key', $keys)
            ->delete();
    }

    protected function group(string $prefix, callable $callback): void
    {
        $prefix = $this->normalizePrefix($prefix);

        if ($prefix === '') {
            $callback();

            return;
        }

        $this->groupStack[] = $prefix;

        try {
            $callback();
        } finally {
            array_pop($this->groupStack);
        }
    }

    protected function addIn(string $prefix, string $locale, string $key, ?string $value): void
    {
        $prefix = $this->normalizePrefix($prefix);

        $this->group($prefix, function () use ($locale, $key, $value) {
            $this->add($locale, $key, $value);
        });
    }

    protected function addManyIn(string $prefix, string|array $localeOrPayload, ?array $keyValues = null): void
    {
        $prefix = $this->normalizePrefix($prefix);

        $this->group($prefix, function () use ($localeOrPayload, $keyValues) {
            $this->addMany($localeOrPayload, $keyValues);
        });
    }

    protected function resolveGroupAndKey(string $key): array
    {
        $resolvedKey = $this->normalizeKey($key);

        if ($this->groupStack === []) {
            return [null, $resolvedKey];
        }

        $group = implode('.', array_filter($this->groupStack, fn ($p) => $p !== ''));

        return [$group !== '' ? $group : null, $resolvedKey];
    }

    protected function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix);
        $prefix = trim($prefix, '.');
        $prefix = preg_replace('/\.+/', '.', $prefix) ?? $prefix;

        return $prefix;
    }

    protected function normalizeKey(string $key): string
    {
        $key = trim($key);
        $key = trim($key, '.');
        $key = preg_replace('/\.+/', '.', $key) ?? $key;

        return $key;
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
