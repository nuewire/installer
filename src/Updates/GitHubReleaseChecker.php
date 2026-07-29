<?php

declare(strict_types=1);

namespace Nuewire\Installer\Updates;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

final class GitHubReleaseChecker
{
    public function __construct(
        private readonly Repository $config,
        private readonly Factory $http,
    ) {
    }

    /**
     * @param array<string, string> $installed package => current version
     * @return array{
     *     packages: array<string, array{latest: string, update_available: bool, status: string}>,
     *     failures: array<string, string>
     * }
     */
    public function check(array $installed): array
    {
        $packages = [];
        $failures = [];

        foreach ($installed as $package => $current) {
            if (! is_string($package) || ! str_starts_with($package, 'nuewire/') || ! is_string($current)) {
                continue;
            }

            try {
                $latest = $this->latestVersion($package);
                $currentComparable = $this->comparableVersion($current);
                $latestComparable = $this->comparableVersion($latest);
                $updateAvailable = $currentComparable !== null
                    && $latestComparable !== null
                    && version_compare($latestComparable, $currentComparable, '>');

                $packages[$package] = [
                    'latest' => $latest,
                    'update_available' => $updateAvailable,
                    'status' => $updateAvailable ? 'update-possible' : 'current',
                ];
            } catch (Throwable $exception) {
                $failures[$package] = $exception->getMessage();
            }
        }

        return ['packages' => $packages, 'failures' => $failures];
    }

    private function latestVersion(string $package): string
    {
        [$owner, $repository] = $this->repository($package);
        $release = $this->request("/repos/{$owner}/{$repository}/releases/latest");

        if ($release->successful()) {
            $tag = trim((string) $release->json('tag_name'));

            if ($this->comparableVersion($tag) !== null) {
                return $tag;
            }
        } elseif ($release->status() !== 404) {
            throw $this->requestFailure($release, $package);
        }

        $tags = $this->request("/repos/{$owner}/{$repository}/tags", ['per_page' => 100]);

        if (! $tags->successful()) {
            throw $this->requestFailure($tags, $package);
        }

        $latest = null;
        $latestComparable = null;

        foreach ((array) $tags->json() as $tag) {
            if (! is_array($tag)) {
                continue;
            }

            $name = trim((string) ($tag['name'] ?? ''));
            $comparable = $this->comparableVersion($name);

            if ($comparable === null || str_contains($comparable, '-')) {
                continue;
            }

            if ($latestComparable === null || version_compare($comparable, $latestComparable, '>')) {
                $latest = $name;
                $latestComparable = $comparable;
            }
        }

        if ($latest === null) {
            throw new RuntimeException("Tidak ada rilis stabil untuk {$package}.");
        }

        return $latest;
    }

    /** @return array{0: string, 1: string} */
    private function repository(string $package): array
    {
        $configured = (array) $this->config->get('nuewire.installer.github.repositories', []);
        $value = trim((string) ($configured[$package] ?? ''));

        if ($value !== '' && preg_match('#^([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)$#', $value, $matches)) {
            return [$matches[1], $matches[2]];
        }

        [$vendor, $name] = array_pad(explode('/', $package, 2), 2, '');
        $owner = trim((string) $this->config->get('nuewire.installer.github.owner', $vendor));

        if ($owner === '') {
            $owner = $vendor;
        }

        if ($owner === '' || $name === '') {
            throw new RuntimeException("Repository GitHub untuk {$package} tidak valid.");
        }

        return [$owner, $name];
    }

    /** @param array<string, scalar> $query */
    private function request(string $path, array $query = []): Response
    {
        $baseUrl = rtrim((string) $this->config->get('nuewire.installer.github.api_url', 'https://api.github.com'), '/');
        $timeout = max(2, (int) $this->config->get('nuewire.installer.github.timeout', 10));
        $token = trim((string) $this->config->get('nuewire.installer.github.token', ''));

        $request = $this->http
            ->baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->withHeaders([
                'User-Agent' => 'nuewire-installer',
                'X-GitHub-Api-Version' => '2022-11-28',
            ]);

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        return $request->get($path, $query);
    }

    private function requestFailure(Response $response, string $package): RuntimeException
    {
        if ($response->status() === 403 && $response->header('X-RateLimit-Remaining') === '0') {
            return new RuntimeException('Batas permintaan GitHub API tercapai. Atur NUEWIRE_GITHUB_TOKEN.');
        }

        if ($response->status() === 404) {
            return new RuntimeException("Repository atau rilis {$package} tidak dapat diakses.");
        }

        return new RuntimeException("GitHub API mengembalikan HTTP {$response->status()} untuk {$package}.");
    }

    private function comparableVersion(string $version): ?string
    {
        $version = trim($version);
        $version = preg_replace('/^v(?=\d)/i', '', $version) ?? $version;

        if (! preg_match('/^\d+(?:\.\d+){1,3}(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version)) {
            return null;
        }

        return $version;
    }
}
