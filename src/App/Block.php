<?php

namespace Trendyminds\Janitor\App;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;

class Block
{
    public static function screenshot(string $block)
    {
        // Sanitize block name to just grab the handle (in case a full path is passed in)
        $block = basename($block);

        // Custom CSS to pass to browsershot
        $customCss = '
            [\@keydown\.slash\.window="show = !show"] {
                display: none !important;
            }
        ';

        $browsershot = Browsershot::url(config('app.url')."/dev/blocks?block=$block&per_page=1")
            ->setNodeModulePath(storage_path('app/janitor/puppeteer/node_modules'))
            ->windowSize(1440, 900)
            ->setOption('addStyleTag', json_encode(['content' => $customCss]));

        // Check if the block exists on the page before trying to take a screenshot.
        $exists = $browsershot
            ->waitUntilNetworkIdle()
            ->evaluate("document.querySelector('main section') !== null");

        if ($exists) {
            $container = AssetContainer::findByHandle('uploads');

            // Save the screenshot to a temporary file first, then move it to the correct location in storage.
            $tempPath = tempnam(sys_get_temp_dir(), 'janitor_').'.webp';

            $browsershot
                ->select('main section')
                ->setScreenshotType('webp')
                ->save($tempPath);

            // Move the file to the correct location in storage
            Storage::disk($container->disk)
                ->putFileAs('_janitor', new File($tempPath), "$block.webp");

            // Delete the temporary file
            unlink($tempPath);

            // Recreate the asset in Statamic
            Asset::make()
                ->container('uploads')
                ->path("_janitor/$block.webp")
                ->save();
        }
    }
}
