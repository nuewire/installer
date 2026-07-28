<?php

declare(strict_types=1);

namespace Nuewire\Installer\Concerns;

use Illuminate\Support\Facades\Lang;

trait TranslatesInstaller
{
    /** @param array<string, scalar> $replace */
    private function translate(string $key, array $replace = []): string
    {
        return Lang::get(
            "nuewire-installer::installer.{$key}",
            $replace,
            (string) config('nuewire.installer.locale', 'id'),
        );
    }
}
