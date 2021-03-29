<?php

namespace CodeDredd\Soap\Handler;

use CodeDredd\Soap\HttpBinding\Converter\Psr7Converter;
use Http\Client\Common\PluginClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\StreamFactoryDiscovery;
use Phpro\SoapClient\Middleware\CollectLastRequestInfoMiddleware;
use Phpro\SoapClient\Middleware\MiddlewareInterface;
use Phpro\SoapClient\Middleware\MiddlewareSupportingInterface;
use Phpro\SoapClient\Soap\Handler\HandlerInterface;
use Phpro\SoapClient\Soap\Handler\LastRequestInfoCollectorInterface;
use Phpro\SoapClient\Soap\HttpBinding\LastRequestInfo;
use Phpro\SoapClient\Soap\HttpBinding\SoapRequest;
use Phpro\SoapClient\Soap\HttpBinding\SoapResponse;
use Psr\Http\Client\ClientInterface;

class HttPlugHandle implements HandlerInterface, MiddlewareSupportingInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var LastRequestInfoCollectorInterface
     */
    private $lastRequestInfoCollector;

    /**
     * @var Psr7Converter
     */
    private $converter;

    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * @var array
     */
    private $requestHeaders = [];

    public function __construct(
        ClientInterface $client,
        Psr7Converter $converter,
        CollectLastRequestInfoMiddleware $lastRequestInfoCollector,
        $requestHeaders = []
    ) {
        $this->client = $client;
        $this->converter = $converter;
        $this->lastRequestInfoCollector = $lastRequestInfoCollector;
        $this->requestHeaders = $requestHeaders;
    }

    public static function createWithDefaultClient(): HttPlugHandle
    {
        return self::createForClient(HttpClientDiscovery::find());
    }

    public static function createForClient(ClientInterface $client, $requestHeaders = []): HttPlugHandle
    {
        return new self(
            $client,
            new Psr7Converter(
                MessageFactoryDiscovery::find(),
                StreamFactoryDiscovery::find()
            ),
            new CollectLastRequestInfoMiddleware(),
            $requestHeaders
        );
    }

    public function addMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewares[$middleware->getName()] = $middleware;
    }

    public function request(SoapRequest $request): SoapResponse
    {
        $client = new PluginClient(
            $this->client,
            array_merge(
                array_values($this->middlewares),
                [$this->lastRequestInfoCollector]
            )
        );

        $psr7Request = $this->converter->convertSoapRequest($request, $this->requestHeaders);
        $psr7Response = $client->sendRequest($psr7Request);

        return $this->converter->convertSoapResponse($psr7Response);
    }

    public function collectLastRequestInfo(): LastRequestInfo
    {
        return $this->lastRequestInfoCollector->collectLastRequestInfo();
    }
}
