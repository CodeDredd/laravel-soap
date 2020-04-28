<?php

namespace CodeDredd\Soap\Tests\Unit\Commands;

use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\Soap\Clients\LaravelSoapClient;
use CodeDredd\Soap\Tests\TestCase;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapEngineFactory;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Phpro\SoapClient\Wsdl\Provider\LocalWsdlProvider;

class GenerateClassMapCommandTest extends TestCase
{
    public function testConsoleCommand()
    {
        $this->artisan('soap:classmap')
            ->expectsQuestion('Please type the wsdl or the name of your client configuration if u have defined in the config "soap.php"', 'laravel_soap')
            ->expectsConfirmation('Do you want to generate all client methods?', 'no')
            ->expectsQuestion('Which method do you want to generate?', 'Get_Customers');
    }

    public function testSoap() {
        $client = $this->app->make(LaravelSoapClient::class);
        $result = $client->Get_Customers([
            'Request_References' => [
                'Customer_Reference' => [
                    'ID' => [
                        '_' => 'CUSTOMER-6-1',
                        'type' => 'Customer_Reference_ID'
                    ]
                ]
            ]
        ])->object();
        dd($result);
    }
}