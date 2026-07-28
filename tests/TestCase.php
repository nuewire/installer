<?php

declare(strict_types=1);

namespace Nuewire\Installer\Tests;

use Nuewire\Installer\InstallerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [InstallerServiceProvider::class];
    }
}
