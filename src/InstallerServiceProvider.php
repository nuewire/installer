<?php

declare(strict_types=1);

namespace Nuewire\Installer;

use Illuminate\Support\ServiceProvider;
use Nuewire\Installer\Catalog\FeatureCatalog;
use Nuewire\Installer\Catalog\RemoteCatalog;
use Nuewire\Installer\Commands\FinalizeCommand;
use Nuewire\Installer\Commands\InstallCommand;
use Nuewire\Installer\Commands\StatusCommand;
use Nuewire\Installer\Commands\UpdateCommand;
use Nuewire\Installer\Composer\ComposerRunner;
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

        // Register before Artisan fires its starting event.
        // This keeps commands available across supported Laravel versions.
        if ($this->app->runningInConsole()) {
            $this->commands(self::COMMANDS);
        }
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(
            __DIR__.'/../resources/lang',
            'nuewire-installer',
        );

        $this->publishes([
            __DIR__.'/../config/nuewire/installer.php' => config_path('nuewire/installer.php'),
        ], 'nuewire-installer-config');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/nuewire/installer'),
        ], 'nuewire-installer-translations');
    }
}
