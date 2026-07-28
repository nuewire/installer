<?php

declare(strict_types=1);

namespace Nuewire\Installer\Tests;

use Illuminate\Support\Facades\Artisan;

final class CommandRegistrationTest extends TestCase
{
    public function test_it_registers_all_nuewire_commands(): void
    {
        self::assertTrue(Artisan::has('nuewire:install'));
        self::assertTrue(Artisan::has('nuewire:status'));
        self::assertTrue(Artisan::has('nuewire:update'));
        self::assertTrue(Artisan::has('nuewire:finalize'));
    }
}
