<?php

namespace CodeDredd\Soap\Client;

use CodeDredd\Soap\Xml\XMLSerializer;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Soap\Psr18Transport\HttpBinding\SoapActionDetector;
use Soap\Xml\Locator\SoapBodyLocator;
use VeeWee\Xml\Dom\Document;

/**
 * Class Request.
 */
class Request
{
    protected RequestInterface $request;

    /**
     * Create a new request instance.
     *
     * @param  RequestInterface  $request
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Get the soap action for soap 1.1 and 1.2.
     */
    public function action(): string
    {
        return Str::remove('"', SoapActionDetector::detectFromRequest($this->request));
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Return complete xml request body.
     */
    public function xmlContent(): string
    {
        return $this->request->getBody()->getContents();
    }

    /**
     * Return request arguments.
     */
    public function arguments(): array
    {
        $doc = Document::fromXmlString($this->xmlContent());
        $arguments = Arr::first(XMLSerializer::domNodeToArray($doc->locate(new SoapBodyLocator())));

        return $arguments ?? [];
    }
}
