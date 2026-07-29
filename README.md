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
php artisan nuewire:install --feature=platform --feature=users --feature=filesystem --no-interaction
php artisan nuewire:update --all --patch-only --no-interaction
```

Composer remains the source of truth. Commit `composer.json` and `composer.lock` after installation or update.

After updating the installer package, clear Laravel's cached package metadata:

```bash
composer dump-autoload
php artisan package:discover --ansi
php artisan optimize:clear
```

## Core support package

`nuewire/support` is installed as a Composer dependency, not as a selectable feature. It contains the reusable random integer ID trait, shared Nuewire paths, and Livewire component registration helpers. It also appears on the status and update pages when installed.

## Users and ACL

The bundled catalog includes:

```text
Platform
Users
ACL
Filesystem
Mail
Pages
Downloads
Media
Newsletter
Platform Logs
Backup
Cache
```

Selecting **Users** also selects Platform. Selecting **ACL** also selects Users and Platform. Selecting **Hero** and **Banner** also selects Platform, Filesystem, and Platform Logs, then registers Content → Website → Hero / Banner. Selecting **Pages** also selects Platform and registers Content → Website → Pages. Selecting **Downloads** also selects Platform, Filesystem, and Platform Logs, then registers Content → Downloads → Documents / Categories. Selecting **Media** also selects Platform, Filesystem, and Platform Logs, then registers Content → Media → Albums. Selecting **Newsletter** also selects Platform and Mail, then registers Plugin → Newsletter → Subscribers / Email Broadcasts. Run `php artisan migrate` when setup reports that Newsletter tables are not ready. Run `php artisan migrate` followed by `php artisan nuewire:page:seed` when setup did not run migrations. Selecting **Platform Logs** also selects Platform and runs the Activitylog publishing step. Selecting **Backup** also selects Platform and Filesystem, then publishes the Spatie backup configuration. Selecting **Cache** also selects Platform and publishes the Nuewire Cache configuration. After Composer finishes, add the required traits to the host `User` model and run:

```bash
php artisan nuewire:users:install --migrate --admin=admin@example.com
php artisan nuewire:acl:install --user=admin@example.com
```

Without ACL, the Users page uses `is_admin`. After ACL is initialized, the same page switches to Spatie roles and permissions.

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

## Update page

When `nuewire/platform` 2 and Livewire are installed, the installer registers **Settings → Package Management → Updates** at `/admin/settings/updates`. The page can check all installed `nuewire/*` packages. Updates are enabled only in the `local` environment by default.

The component can also be mounted directly:

```blade
<livewire:nuewire-updates />
```

```php
'ui' => [
    'allow_updates' => true,
    'allowed_environments' => ['local'],
    'authorization' => [
        'gate' => 'manage-package-updates',
    ],
],
```
