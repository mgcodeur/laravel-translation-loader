<?php

namespace Mgcodeur\LaravelTranslationLoader\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;

class TranslationsStatusCommand extends Command
{
    protected $signature = 'translation:status {--path= : Custom path to translation migrations}';

    protected $description = 'Show the status of translation migrations';

    public function handle(Filesystem $files): int
    {
        $path = $this->option('path') ?: base_path('database/translations');

        if (! $files->isDirectory($path)) {
            $this->warn('Translation migration directory not found.');

            return self::FAILURE;
        }

        $allFiles = collect($files->files($path))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.php'))
            ->sortBy(fn ($f) => $f->getFilename())
            ->values();

        $applied = DB::table('translation_migrations')
            ->pluck('filename')
            ->toArray();

        $this->line('Translation Migration Status');
        $this->line(str_pad('File', 60).' | Status');
        $this->line(str_repeat('-', 75));

        foreach ($allFiles as $file) {
            $filename = $file->getFilename();
            $status = in_array($filename, $applied) ? '<info>✔ Migrated</info>' : '<fg=yellow>⏺ Pending</>';
            $this->line(str_pad($filename, 60)." | {$status}");
        }

        return self::SUCCESS;
    }
}
