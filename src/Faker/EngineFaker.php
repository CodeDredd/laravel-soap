<?php

declare(strict_types=1);

namespace CodeDredd\Soap\Faker;

use CodeDredd\Soap\Xml\XMLSerializer;
use Soap\Engine\Driver;
use Soap\Engine\Engine;
use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\Metadata\Metadata;
use Soap\Engine\Transport;
use Soap\ExtSoapEngine\ExtSoapOptions;

/**
 * Class EngineFaker.
 */
class EngineFaker implements Engine
{
    private Driver $driver;
    private Transport $transport;
    private ExtSoapOptions $options;

    public function __construct(
        Driver $driver,
        Transport $transport,
        ExtSoapOptions $options
    ) {
        $this->driver = $driver;
        $this->transport = $transport;
        $this->options = $options;
    }

    public function request(string $method, array $arguments)
    {
        $request = new SoapRequest(XMLSerializer::arrayToSoapXml($arguments), $this->options->getWsdl(), $method, $this->options->getOptions()['soap_version'] ?? SOAP_1_1);
        $response = $this->transport->request($request);

        return json_decode($response->getPayload());
    }

    public function getMetadata(): Metadata
    {
        return $this->driver->getMetadata();
    }
}
