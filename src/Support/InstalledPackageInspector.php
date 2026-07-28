<?php

declare(strict_types=1);

namespace Btekno\Installer\Support;

use Composer\InstalledVersions;
use Throwable;

final class InstalledPackageInspector
{
    public function installed(string $package): bool
    {
        try {
            return InstalledVersions::isInstalled($package);
        } catch (Throwable) {
            return false;
        }
    }

    public function version(string $package): ?string
    {
        if (! $this->installed($package)) {
            return null;
        }

        return InstalledVersions::getPrettyVersion($package) ?: InstalledVersions::getVersion($package);
    }
}
