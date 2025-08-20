<?php

namespace Mgcodeur\LaravelTranslationLoader\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class LaravelTranslationLoaderCommand extends Command
{
    public $signature = 'laravel-translation-loader:install';

    public $description = 'Setup Laravel Translation Loader';

    private string $repositoryUrl = 'https://github.com/mgcodeur/laravel-translation-loader';

    public function handle(): int
    {
        Artisan::call('vendor:publish', [
            '--tag' => 'translation-loader-migrations',
        ]);

        Artisan::call('vendor:publish', [
            '--tag' => 'translation-loader-config',
        ]);

        $this->info('Laravel Translation Loader has been successfully installed.');

        if ($this->confirm('✨ Would you like to give this repository a star on GitHub?', true)) {
            $this->info('Thank you! Opening the GitHub repository...');
            $this->openInBrowser($this->repositoryUrl);
        }

        return self::SUCCESS;
    }

    private function openInBrowser(string $url): void
    {
        match (PHP_OS_FAMILY) {
            'Windows' => exec('start "" '.escapeshellarg($url).' >NUL 2>&1'),
            'Darwin' => exec('open '.escapeshellarg($url).' >/dev/null 2>&1 &'),
            default => $this->tryLinuxOpen($url),
        };
    }

    private function tryLinuxOpen(string $url): void
    {
        if (shell_exec('command -v xdg-open')) {
            exec('xdg-open '.escapeshellarg($url).' >/dev/null 2>&1 &');
        } elseif (shell_exec('command -v gio')) {
            exec('gio open '.escapeshellarg($url).' >/dev/null 2>&1 &');
        } else {
            $this->warn("Cannot open browser automatically. Please visit: $url");
        }
    }
}
