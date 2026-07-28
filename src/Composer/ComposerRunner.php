<?php

declare(strict_types=1);

namespace Nuewire\Installer\Composer;

use Illuminate\Contracts\Config\Repository;
use JsonException;
use RuntimeException;
use Symfony\Component\Process\Process;

final class ComposerRunner
{
    private ?string $version = null;

    public function __construct(private readonly Repository $config)
    {
    }

    /** @param array<int, string> $arguments @param callable(string): void $output */
    public function run(array $arguments, callable $output): int
    {
        $process = new Process([...$this->binary(), ...$arguments], base_path());
        $process->setTimeout(null);

        if (Process::isTtySupported() && defined('STDIN') && function_exists('stream_isatty') && @stream_isatty(STDIN)) {
            $process->setTty(true);
            $process->run();
        } else {
            $process->run(static function (string $type, string $buffer) use ($output): void {
                $output($buffer);
            });
        }

        return $process->getExitCode() ?? 1;
    }

    public function supportsPatchOnlyUpdate(): bool
    {
        return version_compare($this->version(), '2.8.0', '>=');
    }

    public function version(): string
    {
        if ($this->version !== null) {
            return $this->version;
        }

        $process = new Process([...$this->binary(), '--version', '--no-ansi'], base_path());
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'Composer version could not be detected.');
        }

        $output = trim($process->getOutput());

        if (! preg_match('/Composer(?:\s+version)?\s+(\d+\.\d+\.\d+(?:[-+][^\s]+)?)/i', $output, $matches)) {
            throw new RuntimeException('Composer version could not be detected.');
        }

        return $this->version = $matches[1];
    }

    /** @return array<string, array<string, mixed>> */
    public function outdated(bool $patchOnly = false): array
    {
        $arguments = ['outdated', 'nuewire/*', '--direct', '--format=json', '--no-ansi'];

        if ($patchOnly) {
            $arguments[] = '--patch-only';
        }

        $process = new Process([...$this->binary(), ...$arguments], base_path());
        $process->setTimeout(null);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'Composer could not check updates.');
        }

        try {
            $decoded = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Composer returned invalid update data.', 0, $exception);
        }

        $result = [];

        foreach ((array) ($decoded['installed'] ?? []) as $package) {
            if (! is_array($package) || ! str_starts_with((string) ($package['name'] ?? ''), 'nuewire/')) {
                continue;
            }

            $result[(string) $package['name']] = $package;
        }

        return $result;
    }

    /** @return array<int, string> */
    private function binary(): array
    {
        $configured = trim((string) $this->config->get('nuewire.installer.composer_binary'));

        if ($configured !== '') {
            return str_ends_with(strtolower($configured), '.phar') ? [PHP_BINARY, $configured] : [$configured];
        }

        $localPhar = base_path('composer.phar');

        if (is_file($localPhar)) {
            return [PHP_BINARY, $localPhar];
        }

        return [PHP_OS_FAMILY === 'Windows' ? 'composer.bat' : 'composer'];
    }
}
