<?php

declare(strict_types=1);

namespace Mgcodeur\LaravelTranslationLoader\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class TranslationsRollbackCommand extends Command
{
    protected $signature = 'translation:rollback {--steps=1} {--path=}';

    protected $description = 'Rollback translation migration files (call down()) in reverse order';

    public function handle(Filesystem $files): int
    {
        $path = $this->option('path') ?: base_path('database/translations');
        $steps = max(1, (int) $this->option('steps'));

        if (! $files->isDirectory($path)) {
            $this->info('No translation directory found.');

            return self::SUCCESS;
        }

        $filesList = collect($files->files($path))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.php'))
            ->sortByDesc(fn ($f) => $f->getFilename())
            ->take($steps)
            ->values();

        foreach ($filesList as $file) {
            /** @var \SplFileInfo $file */
            $migration = require $file->getRealPath();
            if (is_object($migration) && method_exists($migration, 'down')) {
                $this->line('Rolling back: '.$file->getFilename());
                $migration->down();
            }
        }

        $this->info('Translations rollback complete.');

        return self::SUCCESS;
    }
}
