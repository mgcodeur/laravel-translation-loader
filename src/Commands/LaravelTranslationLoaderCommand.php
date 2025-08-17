<?php

namespace Mgcodeur\LaravelTranslationLoader\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class LaravelTranslationLoaderCommand extends Command
{
    public $signature = 'laravel-translation-loader:install';

    public $description = 'Setup Laravel Translation Loader';

    public function handle(): int
    {
        Artisan::call('vendor:publish', [
            '--tag' => 'translation-loader-migrations',
        ]);

        Artisan::call('vendor:publish', [
            '--tag' => 'translation-loader-config',
        ]);

        $this->info('Laravel Translation Loader has been successfully installed.');

        return self::SUCCESS;
    }
}
