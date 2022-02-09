<?php

declare(strict_types=1);

namespace CodeDredd\Soap\Faker;

use CodeDredd\Soap\Xml\XMLSerializer;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Phpro\SoapClient\Soap\Engine\DriverInterface;
use Phpro\SoapClient\Soap\Engine\EngineInterface;
use Phpro\SoapClient\Soap\Engine\Metadata\MetadataInterface;
use Phpro\SoapClient\Soap\Handler\HandlerInterface;
use Phpro\SoapClient\Soap\HttpBinding\LastRequestInfo;
use Phpro\SoapClient\Soap\HttpBinding\SoapRequest;

/**
 * Class EngineFaker.
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
     * @var ExtSoapOptions
     */
    private $options;

    /**
     * EngineFaker constructor.
     *
     * @param  DriverInterface  $driver
     * @param  HandlerInterface  $handler
     * @param  ExtSoapOptions  $options
     */
    public function __construct(
        DriverInterface $driver,
        HandlerInterface $handler,
        ExtSoapOptions $options
    ) {
        $this->driver = $driver;
        $this->handler = $handler;
        $this->options = $options;
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
        $options = $this->options->getOptions();
        $request = new SoapRequest(XMLSerializer::arrayToSoapXml($arguments), $this->options->getWsdl(), $method, $options['soap_version'] ?? SOAP_1_1);
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
