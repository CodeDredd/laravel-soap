<?php

declare(strict_types=1);

namespace CodeDredd\Soap\Driver\ExtSoap;

use Phpro\SoapClient\Soap\Driver\ExtSoap\AbusedClient as PhproAbusedClient;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Phpro\SoapClient\Soap\HttpBinding\SoapRequest;
use Phpro\SoapClient\Xml\SoapXml;

class AbusedClient extends PhproAbusedClient
{
    public static function createFromOptions(ExtSoapOptions $options): PhproAbusedClient
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
}
