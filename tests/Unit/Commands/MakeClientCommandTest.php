<?php

namespace CodeDredd\Soap\Tests\Unit\Commands;

use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\Soap\Clients\LaravelSoapClient;
use CodeDredd\Soap\Tests\TestCase;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapEngineFactory;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Phpro\SoapClient\Wsdl\Provider\LocalWsdlProvider;

class MakeClientCommandTest extends TestCase
{
    public function testConsoleCommand()
    {
        $this->artisan('soap:make:client --dry-run')
            ->expectsQuestion('Please type the wsdl or the name of your client configuration if u have defined one in the config "soap.php"', 'laravel_soap');
    }
}
