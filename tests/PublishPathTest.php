<?php

declare(strict_types=1);

namespace Nuewire\Installer\Tests;

use Illuminate\Support\ServiceProvider;
use Nuewire\Installer\InstallerServiceProvider;

final class PublishPathTest extends TestCase
{
    public function test_translations_use_the_shared_vendor_directory(): void
    {
        $paths = ServiceProvider::pathsToPublish(
            InstallerServiceProvider::class,
            'nuewire-installer-translations',
        );

        self::assertContains(
            lang_path('vendor/nuewire/installer'),
            array_values($paths),
        );

        $viewPaths = ServiceProvider::pathsToPublish(
            InstallerServiceProvider::class,
            'nuewire-installer-views',
        );

        self::assertContains(
            resource_path('views/vendor/nuewire/installer'),
            array_values($viewPaths),
        );
    }
}
