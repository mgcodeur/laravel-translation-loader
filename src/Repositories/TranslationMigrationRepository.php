<?php

declare(strict_types=1);

namespace Mgcodeur\LaravelTranslationLoader\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TranslationMigrationRepository
{
    private const TABLE = 'translation_migrations';

    /** @return Collection<int, string> */
    public function getRanFilenames(): Collection
    {
        return DB::table(self::TABLE)->orderBy('id')->pluck('filename');
    }

    public function getLastBatchNumber(): int
    {
        return (int) (DB::table(self::TABLE)->max('batch') ?? 0);
    }

    public function logAsRan(string $filename, int $batch): void
    {
        DB::table(self::TABLE)->insert([
            'filename' => $filename,
            'batch' => $batch,
            'ran_at' => now(),
        ]);
    }

    public function deleteLog(string $filename): void
    {
        DB::table(self::TABLE)->where('filename', $filename)->delete();
    }

    /** @return array<int, array{filename:string,batch:int}> */
    public function getLastBatch(): array
    {
        $last = $this->getLastBatchNumber();
        if ($last === 0) {
            return [];
        }

        return DB::table(self::TABLE)
            ->where('batch', $last)
            ->orderByDesc('id')
            ->get(['filename', 'batch'])
            ->map(fn ($r) => ['filename' => (string) $r->filename, 'batch' => (int) $r->batch])
            ->all();
    }

    /** @return array<int, string> */
    public function getLastN(int $steps): array
    {
        return DB::table(self::TABLE)
            ->orderByDesc('id')
            ->limit($steps)
            ->pluck('filename')
            ->map(fn ($f) => (string) $f)
            ->all();
    }
}
