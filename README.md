# Nuewire Installer

Install and update Nuewire packages from one Artisan command.

## Install

```bash
composer require --dev nuewire/installer
php artisan nuewire:install
```

Commands:

```bash
php artisan nuewire:install
php artisan nuewire:status --updates
php artisan nuewire:update
```

Non-interactive use:

```bash
php artisan nuewire:install --feature=platform --feature=filesystem --no-interaction
php artisan nuewire:update --all --patch-only --no-interaction
```

Composer remains the source of truth. Commit `composer.json` and `composer.lock` after installation or update.

After updating the installer package, clear Laravel's cached package metadata:

```bash
composer dump-autoload
php artisan package:discover --ansi
php artisan optimize:clear
```

## Add a feature

Add a definition to `config/nuewire/installer.php`:

```php
'queue' => [
    'package' => 'nuewire/queue',
    'constraint' => '^1.0',
    'label' => ['id' => 'Queue', 'en' => 'Queue'],
    'description' => ['id' => 'Konfigurasi queue.', 'en' => 'Queue configuration.'],
    'recommended' => false,
    'order' => 40,
],
```

An optional signed remote catalog can be enabled with `NUEWIRE_CATALOG_ENABLED`, `NUEWIRE_CATALOG_URL`, and `NUEWIRE_CATALOG_PUBLIC_KEY`.

## Kompatibilitas Composer

Instalasi dan update umum mendukung Composer 2.x. Opsi `--patch-only` memerlukan Composer 2.8 atau lebih baru.
