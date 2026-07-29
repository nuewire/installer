<div class="nui-updates">
    <style>
        .nui-updates{font-family:ui-sans-serif,system-ui,-apple-system,sans-serif;color:var(--bp-text,#172033)}.nui-card{overflow:hidden;border:1px solid var(--bp-border,#e3e8ef);border-radius:16px;background:var(--bp-panel,#fff)}.nui-head,.nui-actions{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 20px}.nui-head{border-bottom:1px solid var(--bp-border,#e3e8ef)}.nui-head h2{margin:0;font-size:20px}.nui-head p{margin:4px 0 0;color:var(--bp-muted,#667085);font-size:13px}.nui-button{border:1px solid var(--bp-border,#e3e8ef);border-radius:10px;background:var(--bp-panel,#fff);color:var(--bp-text,#172033);padding:9px 13px;font-weight:700;cursor:pointer}.nui-button.primary{border-color:var(--bp-accent,#172033);background:var(--bp-accent,#172033);color:var(--bp-panel,#fff)}.nui-button:disabled{opacity:.5;cursor:not-allowed}.nui-note{margin:14px 20px 0;padding:10px 12px;border-radius:10px;background:var(--bp-bg,#f4f6f8);color:var(--bp-muted,#667085);font-size:12px}.nui-alert{margin:14px 20px 0;padding:10px 12px;border-radius:10px;font-size:13px}.nui-success{background:#ecfdf3;color:#067647}.nui-danger{background:#fef3f2;color:#b42318}.nui-warning{background:#fffbeb;color:#92400e}.nui-table-wrap{overflow:auto;padding:16px 20px}.nui-table{width:100%;border-collapse:collapse}.nui-table th,.nui-table td{padding:12px 10px;border-bottom:1px solid var(--bp-border,#e3e8ef);text-align:left;vertical-align:middle;font-size:13px}.nui-table th{color:var(--bp-muted,#667085);font-size:11px;text-transform:uppercase;letter-spacing:.04em}.nui-package strong{display:block}.nui-package span{display:block;margin-top:3px;color:var(--bp-muted,#667085);font-size:11px}.nui-version{white-space:nowrap;font-variant-numeric:tabular-nums}.nui-badge{display:inline-flex;padding:4px 7px;border-radius:999px;background:var(--bp-bg,#f4f6f8);font-size:11px;font-weight:700}.nui-badge.update{background:#fff4e5;color:#b54708}.nui-actions{border-top:1px solid var(--bp-border,#e3e8ef)}.nui-actions small{color:var(--bp-muted,#667085)}.nui-output{margin:14px 20px 20px}.nui-output summary{cursor:pointer;font-size:12px;font-weight:700}.nui-output pre{max-height:300px;overflow:auto;padding:12px;border-radius:10px;background:#0d1117;color:#e6edf3;font:11px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace;white-space:pre-wrap}.nui-empty{padding:32px 20px;text-align:center;color:var(--bp-muted,#667085)}@media(max-width:720px){.nui-head,.nui-actions{align-items:stretch;flex-direction:column}.nui-button{width:100%}.nui-table th:nth-child(4),.nui-table td:nth-child(4){display:none}}
    </style>

    <div class="nui-card">
        <header class="nui-head">
            <div>
                <h2>{{ __('nuewire-installer::installer.ui.title', [], $locale) }}</h2>
                <p>{{ __('nuewire-installer::installer.ui.subtitle', [], $locale) }}</p>
            </div>
            <button class="nui-button" type="button" wire:click="checkUpdates" wire:loading.attr="disabled" wire:target="checkUpdates,updateSelected">
                <span wire:loading.remove wire:target="checkUpdates">{{ __('nuewire-installer::installer.ui.check', [], $locale) }}</span>
                <span wire:loading wire:target="checkUpdates">{{ __('nuewire-installer::installer.ui.checking', [], $locale) }}</span>
            </button>
        </header>

        <p class="nui-note">{{ __('nuewire-installer::installer.ui.source_note', [], $locale) }}</p>
        @if(! $updatesEnabled)<p class="nui-note">{{ __('nuewire-installer::installer.ui.read_only', [], $locale) }}</p>@endif
        @if($checkedAt)<p class="nui-note">{{ __('nuewire-installer::installer.ui.checked_at', ['time' => $checkedAt], $locale) }}</p>@endif
        @if($status)<p class="nui-alert nui-success">{{ $status }}</p>@endif
        @if($warning)<p class="nui-alert nui-warning">{{ $warning }}</p>@endif
        @if($error)<p class="nui-alert nui-danger">{{ $error }}</p>@endif

        @if($packages === [])
            <div class="nui-empty">{{ __('nuewire-installer::installer.ui.empty', [], $locale) }}</div>
        @else
            <div class="nui-table-wrap">
                <table class="nui-table">
                    <thead><tr><th></th><th>{{ __('nuewire-installer::installer.ui.package', [], $locale) }}</th><th>{{ __('nuewire-installer::installer.ui.current', [], $locale) }}</th><th>{{ __('nuewire-installer::installer.ui.latest', [], $locale) }}</th><th>{{ __('nuewire-installer::installer.ui.state', [], $locale) }}</th></tr></thead>
                    <tbody>
                    @foreach($packages as $package)
                        <tr wire:key="update-{{ $package['package'] }}">
                            <td><input type="checkbox" wire:model="selected" value="{{ $package['package'] }}" @disabled(! $updatesEnabled || ! $package['update_available']) aria-label="{{ $package['label'] }}"></td>
                            <td class="nui-package"><strong>{{ $package['label'] }}</strong><span>{{ $package['package'] }}</span></td>
                            <td class="nui-version">{{ $package['current'] }}</td>
                            <td class="nui-version">{{ $package['latest'] }}</td>
                            <td><span class="nui-badge {{ $package['update_available'] ? 'update' : '' }}">{{ $package['update_available'] ? __('nuewire-installer::installer.ui.available', [], $locale) : __('nuewire-installer::installer.ui.current_state', [], $locale) }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <footer class="nui-actions">
            <small>{{ __('nuewire-installer::installer.ui.composer_note', [], $locale) }}</small>
            <button class="nui-button primary" type="button" wire:click="updateSelected" wire:loading.attr="disabled" wire:target="checkUpdates,updateSelected" @disabled(! $updatesEnabled)>
                <span wire:loading.remove wire:target="updateSelected">{{ __('nuewire-installer::installer.ui.update', [], $locale) }}</span>
                <span wire:loading wire:target="updateSelected">{{ __('nuewire-installer::installer.ui.updating', [], $locale) }}</span>
            </button>
        </footer>

        @if($output)<details class="nui-output"><summary>{{ __('nuewire-installer::installer.ui.output', [], $locale) }}</summary><pre>{{ $output }}</pre></details>@endif
    </div>
</div>
