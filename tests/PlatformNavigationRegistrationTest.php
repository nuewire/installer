<?php

declare(strict_types=1);

namespace Nuewire\Installer\Tests;

final class PlatformNavigationRegistrationTest extends TestCase
{
    public function test_updates_page_is_registered_under_settings_package_management(): void
    {
        $abstract = 'Nuewire\\Platform\\Navigation\\NavigationRegistry';
        $this->app->singleton($abstract, static fn (): FakeInstallerNavigationRegistry => new FakeInstallerNavigationRegistry());

        /** @var FakeInstallerNavigationRegistry $registry */
        $registry = $this->app->make($abstract);

        self::assertArrayHasKey('installer.updates', $registry->pages);
        self::assertSame('settings', $registry->pages['installer.updates']['area']);
        self::assertSame('package-management', $registry->pages['installer.updates']['group']);
        self::assertSame('updates', $registry->pages['installer.updates']['slug']);
        self::assertSame('nuewire-updates', $registry->pages['installer.updates']['component']);
    }
}

final class FakeInstallerNavigationRegistry
{
    /** @var array<string, array<string, mixed>> */
    public array $pages = [];

    /** @param array<string, mixed> $area */
    public function registerArea(string $id, array $area = []): self
    {
        return $this;
    }

    /** @param array<string, mixed> $page */
    public function register(string $id, array $page): self
    {
        $this->pages[$id] = $page;

        return $this;
    }
}
