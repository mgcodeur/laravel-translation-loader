# ⚡ Laravel Translation Loader

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mgcodeur/laravel-translation-loader.svg?style=flat-square)](https://packagist.org/packages/mgcodeur/laravel-translation-loader)
[![Tests](https://img.shields.io/github/actions/workflow/status/mgcodeur/laravel-translation-loader/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mgcodeur/laravel-translation-loader/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Code Style](https://img.shields.io/github/actions/workflow/status/mgcodeur/laravel-translation-loader/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mgcodeur/laravel-translation-loader/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Downloads](https://img.shields.io/packagist/dt/mgcodeur/laravel-translation-loader.svg?style=flat-square)](https://packagist.org/packages/mgcodeur/laravel-translation-loader)

Manage Laravel translations in a database with version-controlled migration files. This package streamlines multilingual app development, letting you create, update, and maintain translations without touching JSON or PHP files.

## 🚀 Quick Start

Get up and running in three steps.

### 1. Install the Package

Add the package to your Laravel project:

```bash
composer require mgcodeur/laravel-translation-loader
```

### 2. Publish Assets

Publish configuration and migration files:

```bash
php artisan laravel-translation-loader:install
```

This generates:

- `config/translation-loader.php` (customizable settings)
- `database/migrations/create_translations_table.php` (schema for translation tables)
- `database/migrations/create_languages_table.php` (schema for language management)
- `database/migrations/create_translation_migrations_table.php` (tracks applied translation migrations)

### 3. Run Migrations

Run the migrations:

```bash
php artisan migrate
```

## 🛠️ Creating Translation Migrations

Generate a migration to define translations:

```bash
php artisan make:translation welcome
```

This creates a file in `database/translations/`:

```php
<?php

use Mgcodeur\LaravelTranslationLoader\Translations\TranslationMigration;

return new class extends TranslationMigration
{
    public function up(): void
    {
        $this->add('en', 'welcome.title', 'Welcome to Our App');
        $this->add('fr', 'welcome.title', 'Bienvenue dans notre application');
        $this->add('es', 'welcome.title', 'Bienvenido a nuestra aplicación');
    }

    public function down(): void
    {
        $this->delete('en', 'welcome.title');
        $this->delete('fr', 'welcome.title');
        $this->delete('es', 'welcome.title');
    }
};
```

### Apply or Revert Migrations

Run all pending translation migrations:

```bash
php artisan translation:migrate
```

Rollback the last migration:

```bash
php artisan translation:rollback
```

## 🎯 Using Translations

Access translations as you would with standard Laravel language files:

```php
// In controllers, views, or anywhere
echo __('welcome.title'); // Outputs: "Welcome to Our App" (if en is active)
```

## 📦 Bonus Features

### Check Migration Status

View the status of translation migrations:

```bash
php artisan translation:status
```

**Example Output:**

```plaintext
+-------------------------+----------+
| Migration               | Status   |
+-------------------------+----------+
| welcome                 | Migrated |
| auth                    | Pending  |
+-------------------------+----------+
```

### Generate Language Files

Export database translations to Laravel’s `lang` directory:

```bash
php artisan translation:generate
```

This creates files like:

```plaintext
lang/
├── en.json
├── fr.json
├── es.json
└── ...
```

**Tip**: Customize the output path in `config/translation-loader.php`.

## ⚙️ Configuration

Customize settings in `config/translation-loader.php`

### Fallbacks

If a translation is missing, Laravel will fall back to the default language defined in your `config/app.php`.

## ⚠️ Troubleshooting

For more help, check [GitHub Issues](https://github.com/mgcodeur/laravel-translation-loader/issues) or open a new issue.

## ❓ FAQ

**Q: Can I use this with existing JSON/PHP translation files?**  
A: Yes! The package works alongside file-based translations.

**Q: How does caching work?**  
A: Translations are cached for performance. Automatically cleared when migrations are applied or rolled back.

## 📜 License

Licensed under the [MIT License](./LICENSE.md).

## ❤️ Support the Project

If this package saves you time, show some love:

- ⭐ Star the [GitHub repo](https://github.com/mgcodeur/laravel-translation-loader)
- 📢 Share it with your network
- 💸 Sponsor development via [GitHub Sponsors](https://github.com/sponsors/mgcodeur)

**Built for Laravel developers, by Laravel developers.** Let’s make multilingual apps effortless! 🚀
