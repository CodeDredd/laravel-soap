<?php

declare(strict_types=1);

namespace CodeDredd\Soap\Faker;

use CodeDredd\Soap\XML\XMLSerializer;
use Phpro\SoapClient\Soap\Engine\DriverInterface;
use Phpro\SoapClient\Soap\Engine\EngineInterface;
use Phpro\SoapClient\Soap\Engine\Metadata\MetadataInterface;
use Phpro\SoapClient\Soap\Handler\HandlerInterface;
use Phpro\SoapClient\Soap\HttpBinding\LastRequestInfo;
use Phpro\SoapClient\Soap\HttpBinding\SoapRequest;
use Phpro\SoapClient\Xml\SoapXml;

class EngineFaker implements EngineInterface
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var HandlerInterface
     */
    private $handler;

    private $wsdl;

    public function __construct(
        DriverInterface $driver,
        HandlerInterface $handler,
        $wsdl = ''
    ) {
        $this->driver = $driver;
        $this->handler = $handler;
        $this->wsdl = $wsdl;
    }

    public function getMetadata(): MetadataInterface
    {
        return $this->driver->getMetadata();
    }

    public function request(string $method, array $arguments)
    {
        $arguments = [
            'SOAP-ENV:Body' => $arguments
        ];
        $xml = new \SimpleXMLElement('<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"/>');
        XMLSerializer::arrayToXml($arguments, $xml);
        $request = new SoapRequest($xml->asXML(), $this->wsdl, $method, 1);
        $response = $this->handler->request($request);

        return json_decode($response->getResponse());
    }

    public function collectLastRequestInfo(): LastRequestInfo
    {
        return $this->handler->collectLastRequestInfo();
    }
}
