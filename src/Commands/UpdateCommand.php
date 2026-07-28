<?php

declare(strict_types=1);

namespace Nuewire\Installer\Commands;

use Nuewire\Installer\Catalog\FeatureCatalog;
use Nuewire\Installer\Concerns\TranslatesInstaller;
use Nuewire\Installer\Composer\ComposerRunner;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Throwable;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\warning;

final class UpdateCommand extends Command
{
    use TranslatesInstaller;

    protected $signature = 'nuewire:update {--feature=* : Feature ID} {--all : Update all available packages} {--patch-only : Only patch updates} {--dry-run : Simulate Composer}';

    protected $description = 'Update installed Nuewire packages';

    public function handle(FeatureCatalog $catalog, ComposerRunner $composer): int
    {
        try {
            if ($this->option('patch-only') && ! $composer->supportsPatchOnlyUpdate()) {
                warning($this->translate('patch_only_requires_composer'));

                return self::FAILURE;
            }

            $outdated = $composer->outdated((bool) $this->option('patch-only'));
        } catch (Throwable $exception) {
            warning($exception->getMessage());

            return self::FAILURE;
        }

        $features = $catalog->managed();
        $available = [];

        foreach ($features as $id => $feature) {
            $package = (string) $feature['package'];

            if (isset($outdated[$package])) {
                $available[$id] = $feature + ['update' => $outdated[$package]];
            }
        }

        if ($available === []) {
            info($this->translate('all_updated'));

            return self::SUCCESS;
        }

        $selected = $this->selection($available);

        if ($selected === null) {
            return self::FAILURE;
        }

        if ($selected === []) {
            info($this->translate('nothing_selected'));

            return self::SUCCESS;
        }

        $arguments = ['update'];

        foreach ($selected as $id) {
            $arguments[] = (string) $available[$id]['package'];
        }

        array_push($arguments, '--with-all-dependencies', '--no-interaction');

        if ($this->option('patch-only')) {
            $arguments[] = '--patch-only';
        }

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
            warning($this->translate('update_failed'));

            return $exit;
        }

        if (! $this->option('dry-run') && $this->runFinalize($selected) !== 0) {
            warning($this->translate('finalize_failed'));

            return self::FAILURE;
        }

        outro($this->translate('update_done'));

        return self::SUCCESS;
    }

    /** @param array<string, array<string, mixed>> $available @return array<int, string>|null */
    private function selection(array $available): ?array
    {
        $requested = array_values(array_filter((array) $this->option('feature')));

        if ($requested !== []) {
            $unknown = array_diff($requested, array_keys($available));

            if ($unknown !== []) {
                warning($this->translate('update_unavailable', ['features' => implode(', ', $unknown)]));

                return null;
            }

            return $requested;
        }

        if ($this->option('all')) {
            return array_keys($available);
        }

        if (! $this->input->isInteractive()) {
            warning($this->translate('non_interactive'));

            return null;
        }

        $options = [];

        foreach ($available as $id => $feature) {
            $update = $feature['update'];
            $options[$id] = sprintf(
                '%s · %s → %s',
                $feature['label'],
                $update['version'] ?? '?',
                $update['latest'] ?? '?',
            );
        }

        return multiselect($this->translate('select_updates'), $options, array_keys($options));
    }

    /** @param array<int, string> $features */
    private function runFinalize(array $features): int
    {
        $arguments = [PHP_BINARY, base_path('artisan'), 'nuewire:finalize', '--no-interaction'];

        foreach ($features as $feature) {
            $arguments[] = '--feature='.$feature;
        }

        $process = new Process($arguments, base_path());
        $process->setTimeout(null);
        $process->run(fn (string $type, string $buffer) => $this->output->write($buffer));

        return $process->getExitCode() ?? 1;
    }
}
