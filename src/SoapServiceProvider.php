<?php

namespace CodeDredd\Soap;

use CodeDredd\Soap\Ray\LaravelRay;
use CodeDredd\Soap\Ray\SoapClientWatcher;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelRay\Watchers\Watcher;

class SoapServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('soap')
            ->hasConfigFile('soap');
    }

    public function packageRegistered()
    {
        $this->registerService();
        $this->registerRay();
    }

    public function packageBooted()
    {
        $this->bootRay();
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

    protected function registerRay()
    {
        if (! class_exists('Spatie\\LaravelRay\\Ray')) {
            return;
        }
        /** @var LaravelRay $macros */
        $macros = app(LaravelRay::class);

        $macros->register();

        $this->app->singleton(SoapClientWatcher::class);
    }

    protected function bootRay()
    {
        if (! class_exists('Spatie\\LaravelRay\\Ray')) {
            return;
        }

        /** @var Watcher $watcher */
        $watcher = app(SoapClientWatcher::class);

        $watcher->register();
    }
}
