<?php

declare(strict_types=1);

namespace Nuewire\Installer;

use Illuminate\Support\ServiceProvider;
use Nuewire\Support\LivewireComponentRegistrar;
use Nuewire\Support\NuewirePaths;
use Nuewire\Installer\Catalog\FeatureCatalog;
use Nuewire\Installer\Catalog\RemoteCatalog;
use Nuewire\Installer\Commands\FinalizeCommand;
use Nuewire\Installer\Commands\InstallCommand;
use Nuewire\Installer\Commands\StatusCommand;
use Nuewire\Installer\Commands\UpdateCommand;
use Nuewire\Installer\Composer\ComposerRunner;
use Nuewire\Installer\Livewire\Updates;
use Nuewire\Installer\Support\InstalledPackageInspector;

final class InstallerServiceProvider extends ServiceProvider
{
    /** @var array<int, class-string> */
    private const COMMANDS = [
        InstallCommand::class,
        StatusCommand::class,
        UpdateCommand::class,
        FinalizeCommand::class,
    ];

    public function register(): void
    {
        $this->replaceConfigRecursivelyFrom(
            __DIR__.'/../config/nuewire/installer.php',
            'nuewire.installer',
        );

        $this->app->singleton(RemoteCatalog::class);
        $this->app->singleton(FeatureCatalog::class);
        $this->app->singleton(InstalledPackageInspector::class);
        $this->app->singleton(ComposerRunner::class);

        if ($this->app->runningInConsole()) {
            $this->commands(self::COMMANDS);
        }

        $this->registerPlatformNavigation();
        $this->registerPlatformDashboard();
        $this->registerAclPermissions();
    }

    public function boot(): void
    {
        $paths = $this->app->make(NuewirePaths::class);
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'nuewire-installer');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'nuewire-installer');
        $this->registerLivewireComponent();

        $this->publishes([
            __DIR__.'/../config/nuewire/installer.php' => $paths->configFile('installer'),
        ], 'nuewire-installer-config');

        $this->publishes([
            __DIR__.'/../resources/lang' => $paths->publishedTranslations('installer'),
        ], 'nuewire-installer-translations');

        $this->publishes([
            __DIR__.'/../resources/views' => $paths->publishedViews('installer'),
        ], 'nuewire-installer-views');
    }

    private function registerLivewireComponent(): void
    {
        $registrar = $this->app->make(LivewireComponentRegistrar::class);
        $registrar->register('nuewire-updates', Updates::class);
    }

    private function registerPlatformNavigation(): void
    {
        if (! (bool) config('nuewire.installer.ui.enabled', true)) {
            return;
        }

        $registryClass = 'Nuewire\Platform\Navigation\NavigationRegistry';

        $this->app->afterResolving($registryClass, static function (object $registry): void {
            if (! method_exists($registry, 'register')) {
                return;
            }

            if (! method_exists($registry, 'registerArea')) {
                $registry->register('updates', [
                    'label' => ['id' => 'Pembaruan', 'en' => 'Updates'],
                    'description' => ['id' => 'Periksa dan perbarui package.', 'en' => 'Check and update packages.'],
                    'group' => ['id' => 'Sistem', 'en' => 'System'],
                    'component' => 'nuewire-updates',
                    'permission' => 'updates.view',
                    'icon' => 'U',
                    'order' => 900,
                ]);

                return;
            }

            $registry->register('installer.updates', [
                'area' => 'settings',
                'group' => 'package-management',
                'slug' => 'updates',
                'label' => ['id' => 'Pembaruan', 'en' => 'Updates'],
                'description' => ['id' => 'Periksa dan perbarui package.', 'en' => 'Check and update packages.'],
                'component' => 'nuewire-updates',
                'permission' => 'updates.view',
                'icon' => 'updates',
                'order' => 10,
            ]);
        });
    }


    private function registerPlatformDashboard(): void
    {
        $registryClass = 'Nuewire\\Platform\\Dashboard\\DashboardRegistry';

        $this->app->afterResolving($registryClass, static function (object $registry): void {
            if (! method_exists($registry, 'register')) {
                return;
            }

            if (method_exists($registry, 'registerGroup')) {
                $registry->registerGroup('packages', [
                    'label' => ['id' => 'Package', 'en' => 'Packages'],
                    'order' => 75,
                ]);
            }

            $registry->register('installer.package-status', [
                'group' => 'packages',
                'label' => ['id' => 'Status Package Nuewire', 'en' => 'Nuewire Package Status'],
                'description' => ['id' => 'Versi package suite yang terpasang pada composer.lock.', 'en' => 'Installed suite package versions from composer.lock.'],
                'type' => 'table',
                'permission' => 'updates.view',
                'width' => 8,
                'default' => false,
                'cache_ttl' => 300,
                'cache_scope' => 'global',
                'resolver' => static function (object $context): array {
                    $catalog = app(\Nuewire\Installer\Catalog\FeatureCatalog::class)->managed($context->locale);
                    $inspector = app(\Nuewire\Installer\Support\InstalledPackageInspector::class);
                    $rows = [];
                    foreach ($catalog as $feature) {
                        $package = (string) ($feature['package'] ?? '');
                        $version = $package !== '' ? $inspector->version($package) : null;
                        $rows[] = [
                            'feature' => (string) ($feature['label'] ?? $feature['id'] ?? $package),
                            'package' => $package,
                            'version' => $version ?? ($context->locale === 'en' ? 'Not installed' : 'Tidak terpasang'),
                        ];
                    }

                    return [
                        'columns' => [
                            ['key' => 'feature', 'label' => $context->locale === 'en' ? 'Feature' : 'Fitur'],
                            ['key' => 'package', 'label' => 'Package'],
                            ['key' => 'version', 'label' => $context->locale === 'en' ? 'Version' : 'Versi'],
                        ],
                        'rows' => $rows,
                        'url' => $context->route('settings', 'updates'),
                    ];
                },
                'order' => 10,
            ]);

            $registry->register('installer.installation-health', [
                'group' => 'packages',
                'label' => ['id' => 'Kelengkapan Suite', 'en' => 'Suite Coverage'],
                'description' => ['id' => 'Proporsi package katalog yang sudah terpasang.', 'en' => 'Share of catalog packages currently installed.'],
                'type' => 'stat',
                'permission' => 'updates.view',
                'width' => 4,
                'default' => false,
                'cache_ttl' => 300,
                'cache_scope' => 'global',
                'resolver' => static function (object $context): array {
                    $catalog = app(\Nuewire\Installer\Catalog\FeatureCatalog::class)->managed($context->locale);
                    $inspector = app(\Nuewire\Installer\Support\InstalledPackageInspector::class);
                    $total = count($catalog);
                    $installed = 0;
                    foreach ($catalog as $feature) {
                        if ($inspector->installed((string) ($feature['package'] ?? ''))) $installed++;
                    }
                    $rate = $total > 0 ? ($installed / $total) * 100 : 0;

                    return [
                        'value' => number_format($rate, 0).'%',
                        'meta' => "{$installed} / {$total} ".($context->locale === 'en' ? 'packages installed' : 'package terpasang'),
                        'url' => $context->route('settings', 'updates'),
                    ];
                },
                'order' => 20,
            ]);
        });
    }

    private function registerAclPermissions(): void
    {
        $registryClass = 'Nuewire\Acl\Registry\PermissionRegistry';

        $this->app->afterResolving($registryClass, static function (object $registry): void {
            if (! method_exists($registry, 'registerMany')) {
                return;
            }

            $registry->registerMany([
                'updates.view' => ['id' => 'Melihat pembaruan package', 'en' => 'View package updates'],
                'updates.manage' => ['id' => 'Menjalankan pembaruan package', 'en' => 'Run package updates'],
            ], 'installer');
        });
    }
}
