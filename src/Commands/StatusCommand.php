<?php

declare(strict_types=1);

namespace Nuewire\Installer\Commands;

use Nuewire\Installer\Catalog\FeatureCatalog;
use Nuewire\Installer\Concerns\TranslatesInstaller;
use Nuewire\Installer\Composer\ComposerRunner;
use Nuewire\Installer\Support\InstalledPackageInspector;
use Illuminate\Console\Command;
use Throwable;

final class StatusCommand extends Command
{
    use TranslatesInstaller;
    protected $signature = 'nuewire:status {--updates : Check available updates}';
    protected $description = 'Show Nuewire package status';

    public function handle(FeatureCatalog $catalog, InstalledPackageInspector $installed, ComposerRunner $composer): int
    {
        $updates = [];

        if ($this->option('updates')) {
            try {
                $updates = $composer->outdated();
            } catch (Throwable $exception) {
                $this->warn($exception->getMessage());
            }
        }

        $rows = [];

        foreach ($catalog->all() as $feature) {
            $package = (string) $feature['package'];
            $current = $installed->version($package);
            $latest = $updates[$package]['latest'] ?? null;
            $rows[] = [$feature['label'], $package, $current ?: $this->translate('not_installed'), $latest ?: '-'];
        }

        $this->table([
            $this->translate('headers.feature'),
            $this->translate('headers.package'),
            $this->translate('headers.version'),
            $this->translate('headers.update'),
        ], $rows);

        return self::SUCCESS;
    }
}
