<?php

declare(strict_types=1);

namespace Nuewire\Installer\Catalog;

use Illuminate\Contracts\Config\Repository;

final class FeatureCatalog
{
    public function __construct(
        private readonly Repository $config,
        private readonly RemoteCatalog $remote,
    ) {
    }

    /** @return array<string, array<string, mixed>> */
    public function all(?string $locale = null): array
    {
        $locale ??= (string) $this->config->get('nuewire.installer.locale', 'id');
        $features = array_replace((array) $this->config->get('nuewire.installer.features', []), $this->remote->fetch());
        $result = [];

        foreach ($features as $id => $feature) {
            if (! is_string($id) || ! is_array($feature) || ! str_starts_with((string) ($feature['package'] ?? ''), 'nuewire/')) {
                continue;
            }

            $result[$id] = array_replace([
                'id' => $id,
                'constraint' => '^1.0',
                'label' => $id,
                'description' => '',
                'recommended' => false,
                'order' => 100,
            ], $feature, [
                'id' => $id,
                'label' => $this->localize($feature['label'] ?? $id, $locale),
                'description' => $this->localize($feature['description'] ?? '', $locale),
            ]);
        }

        uasort($result, static fn (array $a, array $b): int => [(int) $a['order'], (string) $a['label']] <=> [(int) $b['order'], (string) $b['label']]);

        return $result;
    }

    /** @return array<string, array<string, mixed>> */
    public function managed(?string $locale = null): array
    {
        $locale ??= (string) $this->config->get('nuewire.installer.locale', 'id');
        $manager = (array) $this->config->get('nuewire.installer.manager', []);
        $features = $this->all($locale);

        if (str_starts_with((string) ($manager['package'] ?? ''), 'nuewire/')) {
            $features = ['installer' => array_replace([
                'id' => 'installer',
                'constraint' => '',
                'label' => 'Installer',
                'description' => '',
                'recommended' => false,
                'order' => 0,
            ], $manager, [
                'id' => 'installer',
                'label' => $this->localize($manager['label'] ?? 'Installer', $locale),
                'description' => $this->localize($manager['description'] ?? '', $locale),
            ])] + $features;
        }

        return $features;
    }

    private function localize(mixed $value, string $locale): string
    {
        if (! is_array($value)) {
            return (string) $value;
        }

        return (string) ($value[$locale] ?? $value['id'] ?? $value['en'] ?? reset($value) ?: '');
    }
}
