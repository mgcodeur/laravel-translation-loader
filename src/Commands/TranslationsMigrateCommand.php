<?php

declare(strict_types=1);

namespace Mgcodeur\LaravelTranslationLoader\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Mgcodeur\LaravelTranslationLoader\Repositories\TranslationMigrationRepository;

final class TranslationsMigrateCommand extends Command
{
    protected $signature = 'translation:migrate {--path= : Custom path} {--pretend}';

    protected $description = 'Run pending translation migrations (call up()) and record them';

    public function handle(Filesystem $files, TranslationMigrationRepository $repo): int
    {
        $path = $this->option('path') ?: base_path('database/translations');

        if (! $files->isDirectory($path)) {
            $this->info('No translation directory found.');

            return self::SUCCESS;
        }

        $allFiles = collect($files->files($path))
            ->filter(fn ($f) => Str::endsWith($f->getFilename(), '.php'))
            ->sortBy(fn ($f) => $f->getFilename())
            ->values();

        $ran = $repo->getRanFilenames();
        $pending = $allFiles->reject(fn ($f) => $ran->contains($f->getFilename()))->values();

        if ($pending->isEmpty()) {
            $this->info('Nothing to migrate.');

            return self::SUCCESS;
        }

        $newBatch = $repo->getLastBatchNumber() + 1;
        $pretend = (bool) $this->option('pretend');

        foreach ($pending as $file) {
            /** @var \SplFileInfo $file */
            $filename = $file->getFilename();
            $this->line('Migrating: '.$filename);

            if (! $pretend) {
                $migration = require $file->getRealPath();

                if (is_object($migration) && method_exists($migration, 'up')) {
                    $migration->up();
                } else {
                    $this->warn("Skipped (no up()): {$filename}");

                    continue;
                }

                $repo->logAsRan($filename, $newBatch);
            }
        }

        $this->info('Translations migrated.');

        return self::SUCCESS;
    }
}
