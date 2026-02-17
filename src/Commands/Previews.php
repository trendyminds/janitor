<?php

namespace Trendyminds\Janitor\Commands;

use App\Tags\Blocks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Storage;
use Statamic\Facades\Asset;
use Statamic\Facades\Fieldset;
use Trendyminds\Janitor\App\Block;

class Previews extends Command
{
    protected $signature = 'janitor:previews';

    protected $description = 'Generate preview images for blocks.';

    public function handle()
    {
        $this->preflight();
        $this->clean();
        $this->createScreenshots();
        $this->assignImages();
    }

    /**
     * Checks if the user has everything ready to go for the preview generation
     */
    private function preflight()
    {
        // Check if storage/app/janitor/puppeteer exists. If not, prompt the user to run the install command.
        if (! Storage::disk('local')->exists('janitor/puppeteer')) {
            throw new \Exception('Puppeteer is not installed. Have you ran "php artisan janitor:install" to install it?');
        }
    }

    /**
     * Wipes the existing preview directory and assets in Statamic
     */
    private function clean()
    {
        $this->info('Removing existing block preview images');

        // Delete all existing preview images in Statamic
        Asset::query()
            ->where('container', 'uploads')
            ->where('folder', 'set-previews')
            ->get()
            ->each(fn ($asset) => $asset->delete());

        // Delete the existing storage/app/public/set-previews directory and recreate it
        Storage::disk('public')->deleteDirectory('set-previews');
        Storage::disk('public')->makeDirectory('set-previews');
    }

    /**
     * Loop through all of the blocks in chunks and call the screenshot method to create screenshots
     */
    private function createScreenshots()
    {
        $this->info('Generating block preview images');

        $blocks = (new Blocks)->index()->chunk(8);
        $total = $blocks->count();

        $blocks->each(function ($chunk, $i) use ($total) {
            $count = $i + 1;

            $tasks = $chunk->map(function ($block) {
                return fn () => Block::screenshot($block['handle']);
            })->all();

            $this->info("- Processing group {$count} of {$total}");
            Concurrency::run($tasks);
        });
    }

    /**
     * Assign block images to each of the sets
     */
    private function assignImages()
    {
        $this->info('Assigning images to all blocks');

        $fieldset = Fieldset::find('blocks');
        $contents = $fieldset->contents();

        foreach ($contents['fields'][0]['field']['sets'] as &$group) {
            foreach ($group['sets'] as $handle => &$set) {
                $asset = Asset::query()
                    ->where('container', 'uploads')
                    ->where('path', 'set-previews/'.$handle.'.webp')
                    ->first();

                if ($asset) {
                    $set['image'] = $asset->basename();
                }
            }
        }

        $fieldset->setContents($contents);
        $fieldset->save();
    }
}
