<?php

declare(strict_types=1);

namespace Btekno\Installer\Concerns;

use Illuminate\Support\Facades\Lang;

trait TranslatesInstaller
{
    /** @param array<string, scalar> $replace */
    private function translate(string $key, array $replace = []): string
    {
        return Lang::get(
            "btekno-installer::installer.{$key}",
            $replace,
            (string) config('btekno.installer.locale', 'id'),
        );
    }
}
