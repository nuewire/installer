<?php

declare(strict_types=1);

namespace Nuewire\Installer\Tests;

use Livewire\Livewire;

final class UpdatesComponentRegistrationTest extends TestCase
{
    public function test_updates_component_is_registered_when_livewire_is_available(): void
    {
        $this->app['config']->set('nuewire.installer.ui.authorization.require_authenticated_user', false);

        Livewire::test('nuewire::updates')
            ->assertSet('locale', 'id');
    }
}
