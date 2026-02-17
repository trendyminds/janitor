<?php

namespace Trendyminds\Janitor\Commands;

use App\Tags\Blocks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Statamic\Facades\Asset;
use Statamic\Facades\Fieldset;

class Previews extends Command
{
    protected $signature = 'janitor:previews';

    protected $description = 'Generate preview images for blocks.';

    public function handle()
    {
        $this->preflight();
        $this->getScreenshots();
        $this->assignImages();
    }

    private function preflight()
    {
        // Check if storage/app/janitor/puppeteer exists. If not, prompt the user to run the install command.
        if (! Storage::disk('local')->exists('janitor/puppeteer')) {
            $this->error('Puppeteer is not installed. Have you ran "php artisan janitor:install" to install it?');

            exit(1);
        }
    }

    private function getScreenshots()
    {
        // Delete all existing preview images in Statamic
        Asset::query()
            ->where('container', 'uploads')
            ->where('folder', 'set-previews')
            ->get()
            ->each(fn ($asset) => $asset->delete());

        // Delete the existing storage/app/public/set-previews directory and recreate it
        Storage::disk('public')->deleteDirectory('set-previews');
        Storage::disk('public')->makeDirectory('set-previews');

        // Generate screenshots for each block.
        $this->info('Generating block previews...');

        new Blocks()->index()->each(function ($block) {
            $this->info('✔ '.$block['name']);

            $browsershot = Browsershot::url(config('app.url').'/dev/blocks?block='.$block['handle'].'&per_page=1')
                ->setNodeModulePath(storage_path('app/janitor/puppeteer/node_modules'))
                ->windowSize(1440, 900);

            // Check if the block exists on the page before trying to take a screenshot.
            $exists = $browsershot->evaluate("document.querySelector('main section') !== null");

            if ($exists) {
                $path = Storage::disk('public')->path('set-previews/'.$block['handle'].'.webp');

                $browsershot->select('main section')->save($path);

                // Recreate the asset in Statamic
                Asset::make()
                    ->container('uploads')
                    ->path('set-previews/'.$block['handle'].'.webp')
                    ->save();
            }
        });
    }

    private function assignImages()
    {
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

        $this->info('Assigned all preview images to the blocks fieldset!');
    }
}
