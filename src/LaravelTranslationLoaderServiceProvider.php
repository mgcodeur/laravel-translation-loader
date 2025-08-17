<?php

namespace Mgcodeur\LaravelTranslationLoader;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Mgcodeur\LaravelTranslationLoader\Commands\LaravelTranslationLoaderCommand;

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
            ->hasMigration('create_laravel_translation_loader_table')
            ->hasCommand(LaravelTranslationLoaderCommand::class);
    }
}
