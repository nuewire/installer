<?php

declare(strict_types=1);

namespace Nuewire\Installer\Catalog;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class RemoteCatalog
{
    public function __construct(
        private readonly Repository $config,
        private readonly Factory $http,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @return array<string, array<string, mixed>> */
    public function fetch(): array
    {
        if (! (bool) $this->config->get('nuewire.installer.remote_catalog.enabled', false)) {
            return [];
        }

        $url = trim((string) $this->config->get('nuewire.installer.remote_catalog.url'));
        $publicKey = trim((string) $this->config->get('nuewire.installer.remote_catalog.public_key'));

        if ($url === '' || $publicKey === '') {
            return [];
        }

        try {
            if (! function_exists('sodium_crypto_sign_verify_detached')) {
                throw new RuntimeException('The sodium extension is required for the remote catalog.');
            }

            $response = $this->http
                ->acceptJson()
                ->timeout((int) $this->config->get('nuewire.installer.remote_catalog.timeout', 5))
                ->get($url)
                ->throw();

            $body = $response->body();
            $signature = base64_decode((string) $response->header('X-Nuewire-Signature'), true);
            $key = base64_decode($publicKey, true);

            if ($signature === false || $key === false || ! sodium_crypto_sign_verify_detached($signature, $body, $key)) {
                throw new RuntimeException('Remote catalog signature is invalid.');
            }

            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($decoded) || (int) ($decoded['schema'] ?? 0) !== 1 || ! is_array($decoded['features'] ?? null)) {
                throw new RuntimeException('Remote catalog schema is invalid.');
            }

            return $this->normalize($decoded['features']);
        } catch (Throwable $exception) {
            $this->logger->warning('Nuewire remote catalog could not be loaded. The bundled catalog is active.', [
                'exception' => $exception::class,
            ]);

            return [];
        }
    }

    /** @param array<int|string, mixed> $features @return array<string, array<string, mixed>> */
    private function normalize(array $features): array
    {
        $normalized = [];

        foreach ($features as $key => $feature) {
            if (! is_array($feature)) {
                continue;
            }

            $id = (string) ($feature['id'] ?? (is_string($key) ? $key : ''));
            $package = (string) ($feature['package'] ?? '');

            if (! preg_match('/^[a-z0-9][a-z0-9-]*$/', $id) || ! str_starts_with($package, 'nuewire/')) {
                continue;
            }

            $normalized[$id] = [
                'package' => $package,
                'constraint' => (string) ($feature['constraint'] ?? '^1.0'),
                'label' => is_array($feature['label'] ?? null) ? $feature['label'] : ['id' => $id, 'en' => $id],
                'description' => is_array($feature['description'] ?? null) ? $feature['description'] : ['id' => '', 'en' => ''],
                'recommended' => (bool) ($feature['recommended'] ?? false),
                'order' => (int) ($feature['order'] ?? 100),
            ];
        }

        return $normalized;
    }
}
