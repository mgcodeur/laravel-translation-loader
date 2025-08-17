<?php

namespace Mgcodeur\LaravelTranslationLoader;

use Mgcodeur\LaravelTranslationLoader\Commands\LaravelTranslationLoaderCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelTranslationLoaderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-translation-loader')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                'create_languages_table',
                'create_translations_table'
            ])
            ->hasCommand(LaravelTranslationLoaderCommand::class);
    }
}
