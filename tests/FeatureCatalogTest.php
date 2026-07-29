<?php

declare(strict_types=1);

namespace Nuewire\Installer\Tests;

use Nuewire\Installer\Catalog\FeatureCatalog;

final class FeatureCatalogTest extends TestCase
{
    public function test_bundled_features_are_available(): void
    {
        $features = app(FeatureCatalog::class)->all('id');

        $this->assertSame(['platform', 'users', 'acl', 'filesystem', 'mail', 'logs', 'backup', 'cache'], array_keys($features));
        $this->assertSame('nuewire/platform', $features['platform']['package']);
        $this->assertSame('^2.1', $features['platform']['constraint']);
        $this->assertSame('nuewire/mail', $features['mail']['package']);
        $this->assertSame('nuewire/logs', $features['logs']['package']);
        $this->assertSame(['platform'], $features['logs']['requires_features']);
        $this->assertSame('nuewire/backup', $features['backup']['package']);
        $this->assertSame(['platform', 'filesystem'], $features['backup']['requires_features']);
        $this->assertSame('nuewire/cache', $features['cache']['package']);
        $this->assertSame(['platform'], $features['cache']['requires_features']);
    }

    public function test_installer_is_included_in_managed_packages(): void
    {
        $features = app(FeatureCatalog::class)->managed('id');

        $this->assertSame(['installer', 'support', 'platform', 'users', 'acl', 'filesystem', 'mail', 'logs', 'backup', 'cache'], array_keys($features));
        $this->assertSame('nuewire/installer', $features['installer']['package']);
        $this->assertSame('nuewire/support', $features['support']['package']);
    }
}
