<?php

declare(strict_types=1);

namespace Btekno\Installer\Tests;

use Btekno\Installer\InstallerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [InstallerServiceProvider::class];
    }
}
