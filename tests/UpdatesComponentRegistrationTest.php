<?php

declare(strict_types=1);

namespace Nuewire\Installer\Tests;

use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

final class UpdatesComponentRegistrationTest extends TestCase
{
    public function test_updates_component_is_registered_when_livewire_is_available(): void
    {
        $this->app['config']->set('nuewire.installer.ui.authorization.require_authenticated_user', false);

        Livewire::test('nuewire-updates')
            ->assertSet('locale', 'id');
    }

    public function test_update_check_uses_github_without_composer_binary(): void
    {
        $this->app['config']->set('nuewire.installer.ui.authorization.require_authenticated_user', false);
        $this->app['config']->set('nuewire.installer.composer_binary', '/missing/composer');
        Http::fake([
            '*' => Http::response(['tag_name' => 'v99.0.0']),
        ]);

        Livewire::test('nuewire-updates')
            ->call('checkUpdates')
            ->assertSet('checked', true)
            ->assertSet('error', null);
    }
}
