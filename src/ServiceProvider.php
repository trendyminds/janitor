<?php

namespace Trendyminds\Janitor;

use Statamic\Providers\AddonServiceProvider;
use Trendyminds\Janitor\Commands\Alphabetize;
use Trendyminds\Janitor\Commands\Install;
use Trendyminds\Janitor\Commands\Previews;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        Alphabetize::class,
        Previews::class,
        Install::class,
    ];
}
