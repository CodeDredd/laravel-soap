<?php

namespace CodeDredd\Soap\Tests;

use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\SoapServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\LaravelRay\RayServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected $loadEnvironmentVariables = true;

    /**
     * Load package service provider.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return string[]
     */
    protected function getPackageProviders($app)
    {
        return [
            SoapServiceProvider::class,
            RayServiceProvider::class,
        ];
    }

    /**
     * Load package alias.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Soap' => Soap::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default wsse
        $app['config']->set('soap.clients.laravel_soap', [
            'base_wsdl' => __DIR__.'/Fixtures/Wsdl/weather.wsdl',
            'with_wsse' => [
                'user_token_name' => 'username',
                'user_token_password' => 'password',
            ],
        ]);
        $app['config']->set('soap.code.path', __DIR__.'/app');
        $app['config']->set('soap.code.namespace', 'App\\Soap');
    }
}
