<?php

namespace CodeDredd\Soap\Tests\Fixtures;

use CodeDredd\Soap\SoapClient;

class CustomSoapClient extends SoapClient
{
    public function buildClient(string $setup = '')
    {
        $this->baseWsdl(__DIR__.'/Wsdl/weather.wsdl');
        $this->withGuzzleClientOptions([
            'handler' => $this->buildHandlerStack(),
        ]);
        $this->refreshEngine();
        $this->isClientBuilded = true;

        return $this;
    }
}
