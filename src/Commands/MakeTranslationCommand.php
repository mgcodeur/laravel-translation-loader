<?php

declare(strict_types=1);

namespace Mgcodeur\LaravelTranslationLoader\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class MakeTranslationCommand extends Command
{
    protected $signature = 'make:translation {name : The translation change name} {--force}';

    protected $description = 'Create a new translation migration file in database/translations';

    public function handle(Filesystem $files): int
    {
        $name = (string) $this->argument('name');

        $path = base_path('database/translations');
        if (! $files->isDirectory($path)) {
            $files->makeDirectory($path, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_".Str::snake($name).'.php';
        $fullPath = $path.DIRECTORY_SEPARATOR.$filename;

        if ($files->exists($fullPath) && ! $this->option('force')) {
            $this->error("File already exists: {$fullPath}");

            return self::FAILURE;
        }

        // Load stub
        $stubPathCandidates = [
            __DIR__.'/../../stubs/translation.stub',
            base_path('stubs/translation.stub'),
        ];

        $stubPath = collect($stubPathCandidates)->first(fn ($p) => $files->exists($p));
        if (! $stubPath) {
            $this->error('translation.stub not found.');

            return self::FAILURE;
        }

        $stub = $files->get($stubPath);

        $className = Str::studly($name);
        $stub = str_replace(
            ['DummyClass', '{{ class }}'],
            [$className, $className],
            $stub
        );

        $files->put($fullPath, $stub);

        $this->info("Created: database/translations/{$filename}");

        return self::SUCCESS;
    }
}
