<?php

namespace CodeDredd\Soap;

use Illuminate\Support\ServiceProvider;

class SoapServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            dirname(__DIR__, 1).'/config/soap.php' => config_path('soap.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerService();
    }

    /**
     * Register Horizon's services in the container.
     *
     * @return void
     */
    protected function registerService()
    {
        $this->app->bind('Soap', function () {
            return new SoapFactory();
        });
    }
}
