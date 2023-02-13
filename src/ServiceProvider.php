<?php

namespace MysqlToSqlite;

use Illuminate\Contracts\Config\Repository;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{

    /**
     * Register paths to be published by the publish command.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/mysql-to-sqlite.php' => config_path('mysql-to-sqlite.php')
        ]);
    }
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/mysql-to-sqlite.php',
            'mysql-to-sqlite'
        );

        $this->commands([
            ConvertToSqlite::class,
        ]);

        // Load the published config file for lumen
        if (method_exists($this->app, 'configure')) {
            $this->app->configure('mysql-to-sqlite');
        }

        $this->app->bind(ConversionConfig::class, function ($app) {
            $config = $app['config']['mysql-to-sqlite'];
            return new ConversionConfig($config, $app->make(Repository::class), $app);
        });
    }
}
