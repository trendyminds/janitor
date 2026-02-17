<?php

namespace Trendyminds\Janitor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class Install extends Command
{
    protected $signature = 'janitor:install {--force : Reinstall Puppeteer}';

    protected $description = 'Installs Puppeteer (required for generating block previews).';

    public function handle(): int
    {
        $puppeteerDirectory = storage_path('app/janitor/puppeteer');

        File::ensureDirectoryExists($puppeteerDirectory);
        File::ensureDirectoryExists($puppeteerDirectory.'/.cache');
        File::put($puppeteerDirectory.'/.gitignore', "*\n!.gitignore\n");

        // skip if already installed unless forced
        if (! $this->option('force') && File::exists($puppeteerDirectory.'/node_modules/puppeteer')) {
            $this->info("Puppeteer already installed at: {$puppeteerDirectory}");

            return self::SUCCESS;
        }

        if ($this->option('force')) {
            File::deleteDirectory($puppeteerDirectory.'/node_modules');
            File::delete($puppeteerDirectory.'/package-lock.json');
        }

        // write package.json
        $packageJson = [
            'private' => true,
            'dependencies' => [
                'puppeteer' => '^24.37.3',
            ],
        ];

        File::put($puppeteerDirectory.'/package.json', json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        // run npm install in that directory
        $this->info("Installing Puppeteer into: {$puppeteerDirectory}");
        $process = new Process(['npm', 'install', '--no-fund', '--no-audit'], $puppeteerDirectory, [
            // ensure chromium cache stays inside this dir
            'PUPPETEER_CACHE_DIR' => $puppeteerDirectory.'/.cache',
            // optional: reduce noise
            'NODE_ENV' => 'development',
        ]);

        $process->setTimeout(600); // puppeteer download can take time
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->error('npm install failed.');

            return self::FAILURE;
        }

        $this->info("Done. Puppeteer installed with Chromium cache at {$puppeteerDirectory}/.cache");

        return self::SUCCESS;
    }
}
