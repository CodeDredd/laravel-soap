<?php

declare(strict_types=1);

namespace CodeDredd\Soap\Faker;

use CodeDredd\Soap\Xml\XMLSerializer;
use Phpro\SoapClient\Soap\Engine\DriverInterface;
use Phpro\SoapClient\Soap\Engine\EngineInterface;
use Phpro\SoapClient\Soap\Engine\Metadata\MetadataInterface;
use Phpro\SoapClient\Soap\Handler\HandlerInterface;
use Phpro\SoapClient\Soap\HttpBinding\LastRequestInfo;
use Phpro\SoapClient\Soap\HttpBinding\SoapRequest;
use Phpro\SoapClient\Xml\SoapXml;

/**
 * Class EngineFaker
 * @package CodeDredd\Soap\Faker
 */
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

    /**
     * @var string
     */
    private $wsdl;

    /**
     * EngineFaker constructor.
     * @param  DriverInterface  $driver
     * @param  HandlerInterface  $handler
     * @param  string  $wsdl
     */
    public function __construct(
        DriverInterface $driver,
        HandlerInterface $handler,
        $wsdl = ''
    ) {
        $this->driver = $driver;
        $this->handler = $handler;
        $this->wsdl = $wsdl;
    }

    /**
     * @return MetadataInterface
     */
    public function getMetadata(): MetadataInterface
    {
        return $this->driver->getMetadata();
    }

    /**
     * @param  string  $method
     * @param  array  $arguments
     * @return mixed
     */
    public function request(string $method, array $arguments)
    {
        $request = new SoapRequest(XMLSerializer::arrayToSoapXml($arguments), $this->wsdl, $method, 1);
        $response = $this->handler->request($request);

        return json_decode($response->getResponse());
    }

    /**
     * @return LastRequestInfo
     */
    public function collectLastRequestInfo(): LastRequestInfo
    {
        return $this->handler->collectLastRequestInfo();
    }
}
