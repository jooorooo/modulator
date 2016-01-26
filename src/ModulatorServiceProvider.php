<?php

namespace Simexis\Modulator;

use Simexis\Modulator\Modules\Modules;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class ModulatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
		$this->publishes([
            __DIR__.'/config/modulator.php' => config_path('modulator.php'),
        ], 'config');
		
        $this->mergeConfigFrom(
            __DIR__.'/config/modulator.php', 'modulator'
        );
		
		$this->app['modules']->boot();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerServices();
		$this->app['modules']->register();
		
		$this->app->register('Simexis\Modulator\Providers\MigrationServiceProvider');
		$this->app->register('Simexis\Modulator\Providers\SeedServiceProvider');
    }

    /**
     * Register the service provider.
     */
    protected function registerServices()
    {
        $this->app->singleton('modules', function ($app) {
            return new Modules($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
			'modules'
		);
    }
}
