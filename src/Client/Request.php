<?php

namespace CodeDredd\Soap\Client;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Soap\Psr18Transport\HttpBinding\SoapActionDetector;
use Soap\Xml\Locator\SoapBodyLocator;
use function VeeWee\Xml\Dom\Configurator\traverse;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Dom\Traverser\Visitor\RemoveNamespaces;
use function VeeWee\Xml\Encoding\element_decode;
use VeeWee\Xml\Encoding\Exception\EncodingException;

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
     *
     * @throws EncodingException
     */
    public function arguments(): array
    {
        $doc = Document::fromXmlString($this->xmlContent());
        $method = $doc->locate(new SoapBodyLocator())?->firstElementChild;

        return Arr::get(element_decode($method, traverse(new RemoveNamespaces())), 'node', []);
    }
}
