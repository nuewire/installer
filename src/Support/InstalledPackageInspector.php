<?php

declare(strict_types=1);

namespace Nuewire\Installer\Support;

use Composer\InstalledVersions;
use Throwable;

final class InstalledPackageInspector
{
    /** @var array<string, string>|null */
    private ?array $lockVersions = null;

    public function installed(string $package): bool
    {
        return $this->version($package) !== null;
    }

    public function version(string $package): ?string
    {
        $versions = $this->lockVersions();

        if (isset($versions[$package])) {
            return $versions[$package];
        }

        try {
            if (! InstalledVersions::isInstalled($package)) {
                return null;
            }

            return InstalledVersions::getPrettyVersion($package) ?: InstalledVersions::getVersion($package);
        } catch (Throwable) {
            return null;
        }
    }

    public function forget(): void
    {
        $this->lockVersions = null;
    }

    /** @return array<string, string> */
    private function lockVersions(): array
    {
        if ($this->lockVersions !== null) {
            return $this->lockVersions;
        }

        $path = base_path('composer.lock');

        if (! is_file($path)) {
            return $this->lockVersions = [];
        }

        try {
            $lock = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return $this->lockVersions = [];
        }

        $versions = [];

        foreach ([...(array) ($lock['packages'] ?? []), ...(array) ($lock['packages-dev'] ?? [])] as $package) {
            if (! is_array($package) || ! is_string($package['name'] ?? null) || ! is_string($package['version'] ?? null)) {
                continue;
            }

            $versions[$package['name']] = $package['version'];
        }

        return $this->lockVersions = $versions;
    }
}
