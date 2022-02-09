<?php

namespace CodeDredd\Soap\HttpBinding\Converter;

use CodeDredd\Soap\HttpBinding\Builder\Psr7RequestBuilder;
use Http\Message\MessageFactory;
use Http\Message\StreamFactory;
use Phpro\SoapClient\Soap\HttpBinding\SoapRequest;
use Phpro\SoapClient\Soap\HttpBinding\SoapResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Psr7Converter.
 */
class Psr7Converter
{
    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var StreamFactory
     */
    private $streamFactory;

    public function __construct(MessageFactory $messageFactory, StreamFactory $streamFactory)
    {
        $this->messageFactory = $messageFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @param  SoapRequest  $request
     * @param  array  $requestHeaders
     * @return RequestInterface
     */
    public function convertSoapRequest(SoapRequest $request, $requestHeaders = []): RequestInterface
    {
        $builder = new Psr7RequestBuilder($this->messageFactory, $this->streamFactory);

        $request->isSOAP11() ? $builder->isSOAP11() : $builder->isSOAP12();
        $builder->setEndpoint($request->getLocation());
        $builder->setSoapAction($request->getAction());
        $builder->setSoapMessage($request->getRequest());
        $builder->setHeaders($requestHeaders);

        return $builder->getHttpRequest();
    }

    /**
     * @param  ResponseInterface  $response
     * @return SoapResponse
     */
    public function convertSoapResponse(ResponseInterface $response): SoapResponse
    {
        return new SoapResponse(
            (string) $response->getBody()
        );
    }
}
