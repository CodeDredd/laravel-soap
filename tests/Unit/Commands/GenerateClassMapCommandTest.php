<?php

namespace CodeDredd\Soap\Tests\Unit\Commands;

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
            ->expectsQuestion('Which method do you want to generate?', 'GetWeatherInformation');
    }
}
