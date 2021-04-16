<?php

declare(strict_types=1);

namespace CodeDredd\Soap\Driver\ExtSoap;

use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptionsResolverFactory;
use Phpro\SoapClient\Soap\HttpBinding\SoapRequest;
use Phpro\SoapClient\Soap\HttpBinding\SoapResponse;
use Phpro\SoapClient\Xml\SoapXml;

class AbusedClient extends \SoapClient
{
    /**
     * @var SoapRequest|null
     */
    protected $storedRequest;

    /**
     * @var SoapResponse|null
     */
    protected $storedResponse;

    // @codingStandardsIgnoreStart
    /**
     * Internal SoapClient property for storing last request.
     *
     * @var string
     */
    protected $__last_request = '';
    // @codingStandardsIgnoreEnd

    // @codingStandardsIgnoreStart
    /**
     * Internal SoapClient property for storing last response.
     *
     * @var string
     */
    protected $__last_response = '';
    // @codingStandardsIgnoreEnd

    public function __construct($wsdl, array $options = [])
    {
        $options = ExtSoapOptionsResolverFactory::createForWsdl($wsdl)->resolve($options);
        parent::__construct($wsdl, $options);
    }

    public static function createFromOptions(ExtSoapOptions $options): self
    {
        return new self($options->getWsdl(), $options->getOptions());
    }

    public function __doRequest($request, $location, $action, $version, $oneWay = 0)
    {
        $xml = SoapXml::fromString($request);
        $action = $action ?? $xml->getBody()->firstChild->localName;
        $this->storedRequest = new SoapRequest($request, $location, $action, $version, (int) $oneWay);

        return $this->storedResponse ? $this->storedResponse->getResponse() : '';
    }

    public function collectRequest(): SoapRequest
    {
        if (! $this->storedRequest) {
            throw new \RuntimeException('No request has been registered yet.');
        }

        return $this->storedRequest;
    }

    public function registerResponse(SoapResponse $response)
    {
        $this->storedResponse = $response;
    }

    public function cleanUpTemporaryState()
    {
        $this->storedRequest = null;
        $this->storedResponse = null;
    }

    public function __getLastRequest(): string
    {
        return $this->__last_request;
    }

    public function __getLastResponse(): string
    {
        return $this->__last_response;
    }
}
