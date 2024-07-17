<?php

namespace Googledrive\Uploadcsv\Providers;

use Illuminate\Support\ServiceProvider;

class uploadcsvProvider extends ServiceProvider
{
    public function boot()
    {
        // Load migrations from the specified directory
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->publishes([
            __DIR__ . '/../../config/gdriveconfig.php' => config_path('gdriveconfig.php'),
        ], 'config');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'uploadcsv');
    }

    public function register()
    {
        //Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/gdriveconfig.php', 'gdriveconfig'
        );
        
    }
}
   