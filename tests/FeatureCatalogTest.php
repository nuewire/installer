<?php

declare(strict_types=1);

namespace Btekno\Installer\Tests;

use Btekno\Installer\Catalog\FeatureCatalog;

final class FeatureCatalogTest extends TestCase
{
    public function test_bundled_features_are_available(): void
    {
        $features = app(FeatureCatalog::class)->all('id');

        $this->assertSame(['platform', 'filesystem', 'mail'], array_keys($features));
        $this->assertSame('btekno/mail', $features['mail']['package']);
    }
    public function test_installer_is_included_in_managed_packages(): void
    {
        $features = app(FeatureCatalog::class)->managed('id');

        $this->assertSame(['installer', 'platform', 'filesystem', 'mail'], array_keys($features));
        $this->assertSame('btekno/installer', $features['installer']['package']);
    }

}
