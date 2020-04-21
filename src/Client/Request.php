<?php

namespace CodeDredd\Soap\Client;

use CodeDredd\Soap\XML\SoapXml;
use CodeDredd\Soap\XML\XMLSerializer;

class Request
{
    /**
     * The underlying PSR request.
     *
     * @var \Psr\Http\Message\RequestInterface
     */
    protected $request;

    /**
     * Create a new request instance.
     *
     * @param  \Psr\Http\Message\RequestInterface  $request
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
        //@todo still need to get the arguments some how
    }

    /**
     * @return string
     */
    public function action(): string
    {
        return $this->request->getHeaderLine('SOAPAction');
    }

    public function getRequest() {
        return $this->request;
    }

    public function arguments(): array {
        $xml = SoapXml::fromString($this->request->getBody()->getContents());
        dd(XMLSerializer::dom2Array($xml->getBody(), true));
        return json_decode(json_encode((array) $xml->getBody()), true);
    }
}
