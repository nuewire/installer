<?php

declare(strict_types=1);

namespace Btekno\Installer\Commands;

use Btekno\Installer\Concerns\TranslatesInstaller;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class FinalizeCommand extends Command
{
    use TranslatesInstaller;

    protected $signature = 'btekno:finalize {--feature=* : Installed or updated feature}';

    protected $description = 'Finalize Btekno package changes';

    public function handle(Filesystem $files): int
    {
        $directory = storage_path('app/private/.btekno');

        if (! $files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0700, true, true);
        }

        @chmod($directory, 0700);

        if ($this->call('optimize:clear') !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->components->info($this->translate('ready'));

        return self::SUCCESS;
    }
}
