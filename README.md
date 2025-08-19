# ⚡ Laravel Translation Loader

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mgcodeur/laravel-translation-loader.svg?style=flat-square)](https://packagist.org/packages/mgcodeur/laravel-translation-loader)
[![Tests](https://img.shields.io/github/actions/workflow/status/mgcodeur/laravel-translation-loader/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mgcodeur/laravel-translation-loader/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Code Style](https://img.shields.io/github/actions/workflow/status/mgcodeur/laravel-translation-loader/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mgcodeur/laravel-translation-loader/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Downloads](https://img.shields.io/packagist/dt/mgcodeur/laravel-translation-loader.svg?style=flat-square)](https://packagist.org/packages/mgcodeur/laravel-translation-loader)

This package lets you manage Laravel translations from the database using simple, versioned migration files.

## 🚀 Installation

```bash
composer require mgcodeur/laravel-translation-loader
```

Install the package:

```bash
php artisan laravel-translation-loader:install
```

This will publish the configuration and migration files.
Run the migrations:

```bash
php artisan migrate
```

Create a Translation Migration

```bash
php artisan make:translation welcome
```

This creates a file in `database/translations/`

```php
<?php

use Mgcodeur\LaravelTranslationLoader\Translations\TranslationMigration;

return new class extends TranslationMigration
{
    public function up(): void
    {
        $this->add('en', 'welcome.title', 'Welcome');
        $this->add('fr', 'welcome.title', 'Bienvenue');
    }

    public function down(): void
    {
        $this->delete('en', 'welcome.title');
        $this->delete('fr', 'welcome.title');
    }
};
```

## ⚡ Running & Rolling Back

Run all pending migrations:

(This will create languages, translations, and translation_migrations tables if they don’t exist.)

```bash
php artisan translation:migrate
```

Rollback the last migration:

```bash
php artisan translation:rollback
```

## 🎯 Usage

You can use the translations in your Laravel application as you would with any other translation file.

```php
__('welcome.title');
```

## 📦 Bonus

You can check translation migration status with:

```bash
php artisan translation:status
```

## 🛠️ Troubleshooting & Support

If you encounter any issues or have questions, feel free to open an issue on [GitHub](https://github.com/mgcodeur/laravel-translation-loader/issues). Contributions, suggestions, and feedback are always welcome!

## 📜 License

This package is open-sourced software licensed under the [MIT license](./LICENSE.md).

## ❤️ Support Us

If you find this package helpful, consider supporting its development by starring the repository, sharing it with others, or sponsoring us. Your support helps us maintain and improve this project!

[![Sponsor](https://img.shields.io/badge/Sponsor-❤️-slateblue?style=flat-square)](https://github.com/sponsors/mgcodeur)
