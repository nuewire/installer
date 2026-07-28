<?php

declare(strict_types=1);

namespace Nuewire\Installer\Tests;

use Livewire\LivewireServiceProvider;
use Nuewire\Installer\InstallerServiceProvider;
use Nuewire\Support\SupportServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SupportServiceProvider::class, LivewireServiceProvider::class, InstallerServiceProvider::class];
    }
}
