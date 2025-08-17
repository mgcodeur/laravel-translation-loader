<?php

namespace Mgcodeur\LaravelTranslationLoader;

use Illuminate\Contracts\Support\DeferrableProvider;
use Mgcodeur\LaravelTranslationLoader\Translations\DatabaseTranslationLoader;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelTranslationLoaderServiceProvider extends PackageServiceProvider implements DeferrableProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-translation-loader')
            ->hasConfigFile()
            ->hasMigrations([
                'create_languages_table',
                'create_translations_table',
            ]);

        $this->registerLoader();
        $this->registerTranslator();
    }

    protected function registerLoader(): void
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new DatabaseTranslationLoader(
                $app['files'],
                $app['path.lang']
            );
        });
    }

    protected function registerTranslator(): void
    {
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];
            $translator = new \Illuminate\Translation\Translator($loader, $app->getLocale());
            $translator->setFallback($app->getFallbackLocale());

            return $translator;
        });
    }

    public function provides(): array
    {
        return ['translator', 'translation.loader'];
    }
}
