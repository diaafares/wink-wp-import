<?php

namespace Wink\WpImport;

use Config;
use Wink\WpImport\Commands\Import;
use Wink\WpImport\DatabaseBuilder;
use Illuminate\Support\ServiceProvider;

class WinkWpImportServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
        $this->publishes([
            $this->configPath() => config_path('wink-wp-import.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                Import::class,
            ]);
        }

        Config::set('database.connections.wordpress',
            Config::get('wink-wp-import.wordpress'));
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}

    /**
     * @return string
     */
    protected function configPath()
    {
        return __DIR__ . '/../config/config.php';
    }
}
