<?php

declare(strict_types=1);

namespace Btekno\Installer\Commands;

use Btekno\Installer\Catalog\FeatureCatalog;
use Btekno\Installer\Concerns\TranslatesInstaller;
use Btekno\Installer\Composer\ComposerRunner;
use Btekno\Installer\Support\InstalledPackageInspector;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Throwable;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\warning;

final class InstallCommand extends Command
{
    use TranslatesInstaller;

    protected $signature = 'btekno:install {--feature=* : Feature ID} {--all : Install all features} {--dry-run : Simulate Composer}';

    protected $description = 'Install Btekno features';

    public function handle(FeatureCatalog $catalog, InstalledPackageInspector $installed, ComposerRunner $composer): int
    {
        $features = $catalog->all();
        $selected = $this->selection($features, $installed);

        if ($selected === null) {
            return self::FAILURE;
        }

        $selected = array_values(array_filter(
            $selected,
            fn (string $id): bool => isset($features[$id])
                && ! $installed->installed((string) $features[$id]['package']),
        ));

        if ($selected === []) {
            info($this->translate('nothing_new'));

            return self::SUCCESS;
        }

        $arguments = ['require'];

        foreach ($selected as $id) {
            $feature = $features[$id];
            $arguments[] = $feature['package'].':'.$feature['constraint'];
        }

        array_push($arguments, '--with-all-dependencies', '--sort-packages', '--no-interaction');

        if ($this->option('dry-run')) {
            $arguments[] = '--dry-run';
        }

        try {
            $exit = $composer->run($arguments, fn (string $buffer) => $this->output->write($buffer));
        } catch (Throwable $exception) {
            warning($exception->getMessage());

            return self::FAILURE;
        }

        if ($exit !== 0) {
            warning($this->translate('install_failed'));

            return $exit;
        }

        if (! $this->option('dry-run') && $this->runFinalize($selected) !== 0) {
            warning($this->translate('finalize_failed'));

            return self::FAILURE;
        }

        outro($this->translate('install_done'));

        return self::SUCCESS;
    }

    /** @param array<string, array<string, mixed>> $features @return array<int, string>|null */
    private function selection(array $features, InstalledPackageInspector $installed): ?array
    {
        $requested = array_values(array_filter((array) $this->option('feature')));

        if ($requested !== []) {
            $unknown = array_diff($requested, array_keys($features));

            if ($unknown !== []) {
                warning($this->translate('unknown_features', ['features' => implode(', ', $unknown)]));

                return null;
            }

            return $requested;
        }

        if ($this->option('all')) {
            return array_keys($features);
        }

        if (! $this->input->isInteractive()) {
            warning($this->translate('non_interactive'));

            return null;
        }

        $options = [];
        $defaults = [];

        foreach ($features as $id => $feature) {
            if ($installed->installed((string) $feature['package'])) {
                continue;
            }

            $options[$id] = $feature['label'].' · '.$feature['description'];

            if ((bool) $feature['recommended']) {
                $defaults[] = $id;
            }
        }

        if ($options === []) {
            info($this->translate('all_installed'));

            return [];
        }

        return multiselect($this->translate('select_features'), $options, $defaults);
    }

    /** @param array<int, string> $features */
    private function runFinalize(array $features): int
    {
        $arguments = [PHP_BINARY, base_path('artisan'), 'btekno:finalize', '--no-interaction'];

        foreach ($features as $feature) {
            $arguments[] = '--feature='.$feature;
        }

        $process = new Process($arguments, base_path());
        $process->setTimeout(null);
        $process->run(fn (string $type, string $buffer) => $this->output->write($buffer));

        return $process->getExitCode() ?? 1;
    }
}
