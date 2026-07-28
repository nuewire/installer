<?php

declare(strict_types=1);

namespace Nuewire\Installer\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Nuewire\Installer\Catalog\FeatureCatalog;
use Nuewire\Installer\Composer\ComposerRunner;
use Nuewire\Installer\Support\InstalledPackageInspector;
use Throwable;

final class Updates extends Component
{
    public string $locale = 'id';

    /** @var array<int, array<string, mixed>> */
    public array $packages = [];

    /** @var array<int, string> */
    public array $selected = [];

    public bool $checked = false;
    public bool $updatesEnabled = false;
    public ?string $checkedAt = null;
    public ?string $status = null;
    public ?string $error = null;
    public ?string $output = null;

    public function mount(FeatureCatalog $catalog, InstalledPackageInspector $inspector): void
    {
        $this->ensureAuthorized();
        $this->locale = $this->resolveLocale();
        $this->updatesEnabled = $this->updatesAreEnabled();
        $this->loadPackages($catalog, $inspector);
    }

    public function checkUpdates(
        FeatureCatalog $catalog,
        InstalledPackageInspector $inspector,
        ComposerRunner $composer,
    ): void {
        $this->ensureAuthorized();
        $this->clearMessages();

        try {
            $outdated = $composer->outdated();
            $inspector->forget();
            $this->loadPackages($catalog, $inspector, $outdated);
            $this->checked = true;
            $this->checkedAt = now()->format('Y-m-d H:i:s');
            $this->status = $outdated === []
                ? $this->translate('ui.status.current')
                : $this->translate('ui.status.checked');
        } catch (Throwable $exception) {
            report($exception);
            $this->error = $this->translate('ui.errors.check').': '.$exception->getMessage();
        }
    }

    public function updateSelected(
        FeatureCatalog $catalog,
        InstalledPackageInspector $inspector,
        ComposerRunner $composer,
    ): void {
        $this->ensureAuthorized('updates.manage');
        $this->clearMessages();

        if (! $this->updatesAreEnabled()) {
            $this->error = $this->translate('ui.errors.disabled');

            return;
        }

        $available = collect($this->packages)
            ->filter(static fn (array $package): bool => (bool) ($package['update_available'] ?? false))
            ->keyBy('package');

        $selected = array_values(array_unique(array_filter(
            $this->selected,
            static fn (mixed $package): bool => is_string($package) && $available->has($package),
        )));

        if ($selected === []) {
            $this->error = $this->translate('ui.errors.select');

            return;
        }

        $arguments = ['update', ...$selected, '--with-all-dependencies', '--no-interaction', '--no-ansi', '--no-progress'];

        if ($composer->supportsMinimalChanges()) {
            $arguments[] = '--minimal-changes';
        }

        try {
            $result = $composer->runCaptured($arguments, (int) config('nuewire.installer.ui.process_timeout', 600));
            $this->output = $this->limitOutput($result['output']);

            if ($result['exit_code'] !== 0) {
                $this->error = $this->translate('ui.errors.update');

                return;
            }

            $featureIds = collect($this->packages)
                ->filter(static fn (array $package): bool => in_array((string) $package['package'], $selected, true))
                ->pluck('id')
                ->values()
                ->all();

            $finalize = $composer->finalize($featureIds, (int) config('nuewire.installer.ui.process_timeout', 600));
            $this->output = $this->limitOutput(trim($this->output."\n".$finalize['output']));

            if ($finalize['exit_code'] !== 0) {
                $this->error = $this->translate('ui.errors.finalize');

                return;
            }

            $inspector->forget();
            $this->selected = [];
            $this->checked = true;
            $this->checkedAt = now()->format('Y-m-d H:i:s');
            $this->loadPackages($catalog, $inspector, $composer->outdated());
            $this->status = $this->translate('ui.status.updated');
        } catch (Throwable $exception) {
            report($exception);
            $this->error = $this->translate('ui.errors.update').': '.$exception->getMessage();
        }
    }

    public function render()
    {
        $this->ensureAuthorized();

        return view('nuewire-installer::livewire.updates');
    }

    /** @param array<string, array<string, mixed>> $outdated */
    private function loadPackages(
        FeatureCatalog $catalog,
        InstalledPackageInspector $inspector,
        array $outdated = [],
    ): void {
        $packages = [];

        foreach ($catalog->managed($this->locale) as $id => $feature) {
            $package = (string) ($feature['package'] ?? '');
            $current = $inspector->version($package);

            if ($current === null) {
                continue;
            }

            $update = $outdated[$package] ?? null;

            $packages[] = [
                'id' => $id,
                'package' => $package,
                'label' => (string) ($feature['label'] ?? $package),
                'description' => (string) ($feature['description'] ?? ''),
                'current' => $current,
                'latest' => is_array($update) ? (string) ($update['latest'] ?? $current) : $current,
                'update_available' => is_array($update),
                'status' => is_array($update) ? (string) ($update['latest-status'] ?? 'update-possible') : 'current',
            ];
        }

        $this->packages = $packages;
    }

    private function updatesAreEnabled(): bool
    {
        if (! (bool) config('nuewire.installer.ui.allow_updates', true)) {
            return false;
        }

        $environments = array_values(array_filter((array) config('nuewire.installer.ui.allowed_environments', ['local'])));

        return $environments === [] || app()->environment(...$environments);
    }

    private function ensureAuthorized(string $permission = 'updates.view'): void
    {
        $authorization = (array) config('nuewire.installer.ui.authorization', []);
        $guard = trim((string) ($authorization['guard'] ?? ''));
        $auth = $guard === '' ? Auth::guard() : Auth::guard($guard);

        if ((bool) ($authorization['require_authenticated_user'] ?? true) && ! $auth->check()) {
            abort(403);
        }

        $user = $auth->user();

        if (app()->bound('nuewire.acl.enabled')) {
            if ($user === null || ! method_exists($user, 'can')) {
                abort(403);
            }

            try {
                abort_unless($user->can($permission), 403);
            } catch (Throwable) {
                abort(403);
            }
        }

        $gate = trim((string) ($authorization['gate'] ?? ''));

        if ($gate !== '' && ($auth->user() === null || Gate::forUser($auth->user())->denies($gate))) {
            abort(403);
        }
    }

    private function resolveLocale(): string
    {
        $locale = (string) config('nuewire.installer.locale', 'id');

        if (app()->bound('session')) {
            $locale = (string) session()->get('nuewire.platform.locale', $locale);
        }

        return in_array($locale, ['id', 'en'], true) ? $locale : 'id';
    }

    private function translate(string $key): string
    {
        return __("nuewire-installer::installer.{$key}", [], $this->locale);
    }

    private function clearMessages(): void
    {
        $this->status = null;
        $this->error = null;
        $this->output = null;
    }

    private function limitOutput(string $output): string
    {
        $output = trim($output);
        $output = (string) preg_replace('#(https?://[^:\s]+:)[^@\s]+@#i', '$1***@', $output);

        return mb_strlen($output) > 20000 ? mb_substr($output, -20000) : $output;
    }
}
