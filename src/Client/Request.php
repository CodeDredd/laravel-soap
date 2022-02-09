<?php

namespace CodeDredd\Soap\Client;

use CodeDredd\Soap\Xml\SoapXml;
use CodeDredd\Soap\Xml\XMLSerializer;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Class Request.
 */
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
    }

    /**
     * Get the soap action for soap 1.1 and 1.2.
     *
     * @return string
     */
    public function action(): string
    {
        $contentType = $this->request->getHeaderLine('Content-Type');
        $soapAction = $this->request->getHeaderLine('SOAPAction');
        if (empty($soapAction)) {
            return Str::of($contentType)->afterLast('action=')->remove('"');
        }

        return $this->request->getHeaderLine('SOAPAction');
    }

    /**
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Return complete xml request body.
     *
     * @return string
     */
    public function xmlContent()
    {
        return $this->request->getBody()->getContents();
    }

    /**
     * Return request arguments.
     *
     * @return array
     */
    public function arguments(): array
    {
        $xml = SoapXml::fromString($this->xmlContent());
        $arguments = Arr::first(XMLSerializer::domNodeToArray($xml->getBody()));

        return $arguments ?? [];
    }
}
