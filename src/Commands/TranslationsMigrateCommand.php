<?php

declare(strict_types=1);

namespace Mgcodeur\LaravelTranslationLoader\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class TranslationsMigrateCommand extends Command
{
    protected $signature = 'translation:migrate {--path= : Custom path}';

    protected $description = 'Run all translation migration files (call up())';

    public function handle(Filesystem $files): int
    {
        $path = $this->option('path') ?: base_path('database/translations');

        if (! $files->isDirectory($path)) {
            $this->info('No translation directory found.');

            return self::SUCCESS;
        }

        $filesList = collect($files->files($path))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.php'))
            ->sortBy(fn ($f) => $f->getFilename())
            ->values();

        foreach ($filesList as $file) {
            /** @var \SplFileInfo $file */
            $migration = require $file->getRealPath();
            if (is_object($migration) && method_exists($migration, 'up')) {
                $this->line('Migrating translation: '.$file->getFilename());
                $migration->up();
            }
        }

        $this->info('Translations migrated.');

        return self::SUCCESS;
    }
}
