<?php
/**
 * Created by PhpStorm.
 * User: Gregor Becker <gregor.becker@getinbyte.com>
 * Date: 15.04.2020
 * Time: 15:30.
 */

namespace CodeDredd\Soap\Tests;

use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\SoapServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Load package service provider.
     * @param  \Illuminate\Foundation\Application $app
     */
    protected function getPackageProviders($app)
    {
        return [SoapServiceProvider::class];
    }

    /**
     * Load package alias.
     * @param  \Illuminate\Foundation\Application $app
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
            'base_wsdl' => 'laravel_soap.wsdl',
            'with_wsse' => [
                'user_token_name'   => 'username',
                'user_token_password'   => 'password',
            ],
        ]);
    }
}
