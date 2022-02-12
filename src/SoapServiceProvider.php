<?php

namespace CodeDredd\Soap;

use CodeDredd\Soap\Commands\MakeClientCommand;
use CodeDredd\Soap\Commands\MakeValidationCommand;
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
        $this->registerPublishing();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/soap.php',
            'soap'
        );

        $this->registerService();
//        $this->registerCommands();
    }

    /**
     * Register Soap's services in the container.
     *
     * @return void
     */
    protected function registerService()
    {
        $this->app->bind('Soap', function () {
            return new SoapFactory();
        });
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    private function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__, 1).'/config/soap.php' => config_path('soap.php'),
            ], 'soap-config');
        }
    }

    /**
     * Register Soap commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
//        $this->commands(MakeClientCommand::class);
//        $this->commands(MakeValidationCommand::class);
    }
}
