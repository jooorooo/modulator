<?php

namespace Simexis\Modulator\Providers;

use Illuminate\Support\ServiceProvider;
use Simexis\Modulator\Database\Console\Seeds\SeedCommand;
use Simexis\Modulator\Database\Console\Seeds\SeederMakeCommand;
use Illuminate\Database\SeedServiceProvider AS BaseSeedServiceProvider;

class SeedServiceProvider extends BaseSeedServiceProvider
{

    /**
     * Register the seed console command.
     *
     * @return void
     */
    protected function registerSeedCommand()
    {
        $this->app->singleton('command.seed', function ($app) {
            return new SeedCommand($app['db']);
        });
    }

    /**
     * Register the seeder generator command.
     *
     * @return void
     */
    protected function registerMakeCommand()
    {
        $this->app->singleton('command.seeder.make', function ($app) {
            return new SeederMakeCommand($app['files'], $app['composer']);
        });
    }

}
