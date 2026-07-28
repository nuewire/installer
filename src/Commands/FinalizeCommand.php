<?php

declare(strict_types=1);

namespace Nuewire\Installer\Commands;

use Nuewire\Installer\Concerns\TranslatesInstaller;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class FinalizeCommand extends Command
{
    use TranslatesInstaller;

    protected $signature = 'nuewire:finalize {--feature=* : Installed or updated feature}';

    protected $description = 'Finalize Nuewire package changes';

    public function handle(Filesystem $files): int
    {
        $directory = storage_path('app/private/.nuewire');

        if (! $files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0700, true, true);
        }

        @chmod($directory, 0700);

        if ($this->call('optimize:clear') !== self::SUCCESS) {
            return self::FAILURE;
        }

        foreach ((array) $this->option('feature') as $feature) {
            $command = config('nuewire.installer.features.'.$feature.'.setup_command');

            if (is_string($command) && $command !== '' && $this->getApplication()?->has($command)) {
                if ($this->call($command) !== self::SUCCESS) {
                    return self::FAILURE;
                }
            }
        }

        $this->components->info($this->translate('ready'));

        return self::SUCCESS;
    }
}
