<?php

declare(strict_types=1);

namespace Btekno\Installer;

use Btekno\Installer\Catalog\FeatureCatalog;
use Btekno\Installer\Catalog\RemoteCatalog;
use Btekno\Installer\Commands\FinalizeCommand;
use Btekno\Installer\Commands\InstallCommand;
use Btekno\Installer\Commands\StatusCommand;
use Btekno\Installer\Commands\UpdateCommand;
use Btekno\Installer\Composer\ComposerRunner;
use Btekno\Installer\Support\InstalledPackageInspector;
use Illuminate\Support\ServiceProvider;

final class InstallerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->replaceConfigRecursivelyFrom(__DIR__.'/../config/btekno/installer.php', 'btekno.installer');
        $this->app->singleton(RemoteCatalog::class);
        $this->app->singleton(FeatureCatalog::class);
        $this->app->singleton(InstalledPackageInspector::class);
        $this->app->singleton(ComposerRunner::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'btekno-installer');
        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, StatusCommand::class, UpdateCommand::class, FinalizeCommand::class]);
        }

        $this->publishes([
            __DIR__.'/../config/btekno/installer.php' => config_path('btekno/installer.php'),
        ], 'btekno-installer-config');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/btekno-installer'),
        ], 'btekno-installer-translations');
    }
}
