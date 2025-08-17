<?php

namespace Mgcodeur\LaravelTranslationLoader\Commands;

use Illuminate\Console\Command;

class LaravelTranslationLoaderCommand extends Command
{
    public $signature = 'laravel-translation-loader';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
