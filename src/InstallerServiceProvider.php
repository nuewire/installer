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
        $registrar->register('nuewire::updates', Updates::class);
    }

    private function registerPlatformNavigation(): void
    {
        if (! (bool) config('nuewire.installer.ui.enabled', true)) {
            return;
        }

        $registryClass = 'Nuewire\\Platform\\Navigation\\NavigationRegistry';

        $this->app->afterResolving($registryClass, static function (object $registry): void {
            if (! method_exists($registry, 'register')) {
                return;
            }

            $registry->register('updates', [
                'label' => ['id' => 'Pembaruan', 'en' => 'Updates'],
                'description' => ['id' => 'Periksa dan perbarui package.', 'en' => 'Check and update packages.'],
                'group' => ['id' => 'Sistem', 'en' => 'System'],
                'component' => 'nuewire::updates',
                'permission' => 'updates.view',
                'icon' => 'U',
                'order' => 900,
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
