<?php

namespace Trendyminds\Janitor\App;

use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Statamic\Facades\Asset;

class Block
{
    public static function screenshot(string $block)
    {
        $browsershot = Browsershot::url(config('app.url')."/dev/blocks?block=$block&per_page=1")
            ->setNodeModulePath(storage_path('app/janitor/puppeteer/node_modules'))
            ->windowSize(1440, 900);

        // Check if the block exists on the page before trying to take a screenshot.
        $exists = $browsershot->evaluate("document.querySelector('main section') !== null");

        if ($exists) {
            $path = Storage::disk('public')->path("set-previews/$block.webp");

            $browsershot->select('main section')->save($path);

            // Recreate the asset in Statamic
            Asset::make()
                ->container('uploads')
                ->path("set-previews/$block.webp")
                ->save();
        }
    }
}
