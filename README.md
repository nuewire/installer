# Btekno Installer

Install and update Btekno packages from one Artisan command.

## Install

```bash
composer require --dev btekno/installer
php artisan btekno:install
```

Commands:

```bash
php artisan btekno:install
php artisan btekno:status --updates
php artisan btekno:update
```

Non-interactive use:

```bash
php artisan btekno:install --feature=platform --feature=filesystem --no-interaction
php artisan btekno:update --all --patch-only --no-interaction
```

Composer remains the source of truth. Commit `composer.json` and `composer.lock` after installation or update.

## Add a feature

Add a definition to `config/btekno/installer.php`:

```php
'queue' => [
    'package' => 'btekno/queue',
    'constraint' => '^1.0',
    'label' => ['id' => 'Queue', 'en' => 'Queue'],
    'description' => ['id' => 'Konfigurasi queue.', 'en' => 'Queue configuration.'],
    'recommended' => false,
    'order' => 40,
],
```

An optional signed remote catalog can be enabled with `BTEKNO_CATALOG_ENABLED`, `BTEKNO_CATALOG_URL`, and `BTEKNO_CATALOG_PUBLIC_KEY`.

## Kompatibilitas Composer

Instalasi dan update umum mendukung Composer 2.x. Opsi `--patch-only` memerlukan Composer 2.8 atau lebih baru.
