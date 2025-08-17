<?php

namespace Mgcodeur\LaravelTranslationLoader\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mgcodeur\LaravelTranslationLoader\LaravelTranslationLoader
 */
class LaravelTranslationLoader extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Mgcodeur\LaravelTranslationLoader\LaravelTranslationLoader::class;
    }
}
