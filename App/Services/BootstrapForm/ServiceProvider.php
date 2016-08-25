<?php

namespace App\Services\BootstrapForm;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/views', 'bootstrap');

        $this->publishes([
            __DIR__.'/config.php' => config_path('bootstrapForm.php')
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

        $this->mergeConfigFrom(__DIR__.'/config.php', 'bootstrapForm');

        $this->app->singelton('bootstrapForm', function($app) {
            return new BootstrapForm($app['html'], $app['form'], $app['config']);
        });
    }

    public function provides(){
        return ['bootstrapForm'];
    }
}
