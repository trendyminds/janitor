<?php

namespace Trendyminds\Janitor\Commands;

use App\Tags\Blocks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
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
            $this->error('Puppeteer is not installed. Have you ran "php artisan janitor:install" to install it?');

            exit(1);
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
            ->where('folder', '_janitor')
            ->get()
            ->each(fn ($asset) => $asset->delete());

        // Delete the existing _janitor directory in the main uploads container and recreate it
        $container = AssetContainer::findByHandle('uploads');
        Storage::disk($container->disk)->deleteDirectory('_janitor');
        Storage::disk($container->disk)->makeDirectory('_janitor');
    }

    /**
     * Loop through all of the blocks in chunks and call the screenshot method to create screenshots
     */
    private function createScreenshots()
    {
        $this->info('Generating block preview images');

        (new Blocks)->index()->each(function ($block) {
            $this->info("- Creating screenshot: {$block['handle']}");
            Block::screenshot($block['handle']);
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
                    ->where('path', '_janitor/'.$handle.'.webp')
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
