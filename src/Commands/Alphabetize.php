<?php

namespace Trendyminds\Janitor\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\Fieldset;

class Alphabetize extends Command
{
    protected $signature = 'janitor';

    protected $description = 'Alphabetize the blocks fieldset.';

    public function handle()
    {
        $fieldset = Fieldset::find('blocks');
        $contents = $fieldset->contents();

        $groups = $contents['fields'][0]['field']['sets'];

        // Sort groups alphabetically by key
        ksort($groups);

        // Sort sets within each group alphabetically by key
        foreach ($groups as &$group) {
            if (isset($group['sets']) && is_array($group['sets'])) {
                ksort($group['sets']);
            }
        }

        $contents['fields'][0]['field']['sets'] = $groups;
        $fieldset->setContents($contents);
        $fieldset->save();

        $this->info('Blocks fieldset has been alphabetized.');
    }
}
