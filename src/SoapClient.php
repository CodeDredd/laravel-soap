<?php

namespace CodeDredd\Soap;

use Closure;
use CodeDredd\Soap\Client\Events\ConnectionFailed;
use CodeDredd\Soap\Client\Events\RequestSending;
use CodeDredd\Soap\Client\Events\ResponseReceived;
use CodeDredd\Soap\Client\Request;
use CodeDredd\Soap\Client\Response;
use CodeDredd\Soap\Driver\ExtSoap\ExtSoapEngineFactory;
use CodeDredd\Soap\Exceptions\NotFoundConfigurationException;
use CodeDredd\Soap\Exceptions\SoapException;
use CodeDredd\Soap\Middleware\CisDhlMiddleware;
use CodeDredd\Soap\Middleware\WsseMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\TransferStats;
use Http\Client\Common\PluginClient;
use Http\Client\Exception\HttpException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Phpro\SoapClient\Type\ResultInterface;
use Phpro\SoapClient\Type\ResultProviderInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Soap\Engine\Engine;
use Soap\Engine\Transport;
use Soap\ExtSoapEngine\AbusedClient;
use Soap\ExtSoapEngine\ExtSoapOptions;
use Soap\ExtSoapEngine\Transport\TraceableTransport;
use Soap\ExtSoapEngine\Wsdl\WsdlProvider;
use Soap\Psr18Transport\Middleware\RemoveEmptyNodesMiddleware;
use Soap\Psr18Transport\Psr18Transport;
use Soap\Psr18Transport\Wsdl\Psr18Loader;
use Soap\Psr18WsseMiddleware\WsaMiddleware;
use Soap\Psr18WsseMiddleware\WsaMiddleware2005;
use Soap\Wsdl\Loader\FlatteningLoader;
use Soap\Wsdl\Loader\StreamWrapperLoader;

/**
 * Class SoapClient.
 */
class SoapClient
{
    use Macroable {
        __call as macroCall;
    }
    use Conditionable;

    protected ClientInterface $client;

    protected PluginClient $pluginClient;

    protected Engine $engine;

    protected array $options = [];

    protected ExtSoapOptions $extSoapOptions;

    protected TraceableTransport|Transport $transport;

    protected array $guzzleClientOptions = [];

    /**
     * The transfer stats for the request.
     */
    protected ?TransferStats $transferStats = null;

    protected string $wsdl = '';

    protected bool $isClientBuilded = false;

    protected array $middlewares = [];

    protected SoapFactory|null $factory;

    protected FlatteningLoader|WsdlProvider $wsdlProvider;

    protected array $cookies = [];

    /**
     * The callbacks that should execute before the request is sent.
     */
    protected \Illuminate\Support\Collection $beforeSendingCallbacks;

    /**
     * The stub callables that will handle requests.
     */
    protected \Illuminate\Support\Collection|null $stubCallbacks;

    /**
     * The sent request object, if a request has been made.
     *
     * @var Request|null
     */
    protected $request;

    /**
     * Create a new Soap Client instance.
     *
     * @param  \CodeDredd\Soap\SoapFactory|null  $factory
     * @return void
     */
    public function __construct(SoapFactory $factory = null)
    {
        $this->factory = $factory;
        $this->client = new Client($this->guzzleClientOptions);
        $this->pluginClient = new PluginClient($this->client, $this->middlewares);
        $this->wsdlProvider = new FlatteningLoader(Psr18Loader::createForClient($this->pluginClient));
        $this->beforeSendingCallbacks = collect([function (Request $request, array $options, SoapClient $soapClient) {
            $soapClient->request = $request;
            $soapClient->cookies = Arr::wrap($options['cookies']);

            $soapClient->dispatchRequestSendingEvent();
        }]);
    }

    public function refreshWsdlProvider()
    {
        $this->wsdlProvider = new FlatteningLoader(Psr18Loader::createForClient($this->pluginClient));

        return $this;
    }

    public function refreshPluginClient(): static
    {
        $this->pluginClient = new PluginClient($this->client, $this->middlewares);

        return $this;
    }

    public function getPluginClient(): PluginClient
    {
        return $this->pluginClient;
    }

    protected function setTransport(Transport $handler = null): static
    {
        $soapClient = AbusedClient::createFromOptions(
            ExtSoapOptions::defaults($this->wsdl, $this->options)
        );
        $transport = $handler ?? Psr18Transport::createForClient($this->pluginClient);

        $this->transport = $handler ?? new TraceableTransport(
            $soapClient,
            $transport
        );

        return $this;
    }

    /**
     * Add the given headers to the request.
     */
    public function withHeaders(array $headers): static
    {
        return $this->withGuzzleClientOptions(array_merge_recursive($this->options, [
            'headers' => $headers,
        ]));
    }

    public function getTransport(): TraceableTransport|Transport
    {
        return $this->transport;
    }

    public function getClient(): Client|ClientInterface
    {
        return $this->client;
    }

    public function withGuzzleClientOptions(array ...$options): static
    {
        $this->guzzleClientOptions = array_merge_recursive($this->guzzleClientOptions, ...$options);
        $this->client = new Client($this->guzzleClientOptions);

        return $this;
    }

    public function getEngine(): Engine
    {
        return $this->engine;
    }

    /**
     * @return $this
     */
    public function withRemoveEmptyNodes()
    {
        $this->middlewares = array_merge_recursive($this->middlewares, [
            new RemoveEmptyNodesMiddleware(),
        ]);

        return $this;
    }

    /**
     * @param  string|array  $username
     * @param  string|null  $password
     * @return $this
     */
    public function withBasicAuth($username, ?string $password = null)
    {
        if (is_array($username)) {
            ['username' => $username, 'password' => $password] = $username;
        }

        $this->withHeaders([
            'Authorization' => sprintf('Basic %s', base64_encode(
                sprintf('%s:%s', $username, $password)
            )),
        ]);

        return $this;
    }

    /**
     * @param  string|array  $user
     * @param  string|null  $signature
     * @return $this
     */
    public function withCisDHLAuth($user, ?string $signature = null)
    {
        if (is_array($user)) {
            ['username' => $user, 'password' => $signature] = $user;
        }

        $this->middlewares = array_merge_recursive($this->middlewares, [
            new CisDhlMiddleware($user, $signature),
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function withWsa()
    {
        $this->middlewares = array_merge_recursive($this->middlewares, [
            new WsaMiddleware(),
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function withWsa2005()
    {
        $this->middlewares = array_merge_recursive($this->middlewares, [
            new WsaMiddleware2005(),
        ]);

        return $this;
    }

    public function withWsse(array $options): static
    {
        $this->middlewares = array_merge_recursive($this->middlewares, [
            new WsseMiddleware($options),
        ]);

        return $this;
    }

    /**
     * Merge new options into the client.
     *
     * @param  array  $options
     * @return $this
     */
    public function withOptions(array $options)
    {
        return tap($this, function ($request) use ($options) {
            return $this->options = array_merge_recursive($this->options, $options);
        });
    }

    /**
     * Merge the given options with the current request options.
     *
     * @param  array  $options
     * @return array
     */
    public function mergeOptions(...$options)
    {
        return array_merge_recursive($this->options, ...$options);
    }

    /**
     * Make it possible to debug the last request.
     */
    public function debugLastSoapRequest(): array
    {
        if ($this->transport instanceof TraceableTransport) {
            $lastRequestInfo = $this->transport->collectLastRequestInfo();

            return [
                'request' => [
                    'headers' => trim($lastRequestInfo->getLastRequestHeaders()),
                    'body' => $lastRequestInfo->getLastRequest(),
                ],
                'response' => [
                    'headers' => trim($lastRequestInfo->getLastResponseHeaders()),
                    'body' => $lastRequestInfo->getLastResponse(),
                ],
            ];
        }

        return [];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return Response
     */
    protected function failedValidation(Validator $validator)
    {
        return Response::fromSoapResponse([
            'success' => false,
            'message' => __('Invalid data.'),
            'errors' => $validator->errors(),
        ]);
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->call($method, $parameters[0] ?? $parameters);
    }

    public function call(string $method, Validator|array $arguments = []): Response
    {
        try {
            if (! $this->isClientBuilded) {
                $this->buildClient();
            }
            $this->refreshEngine();
            if ($arguments instanceof Validator) {
                if ($arguments->fails()) {
                    return $this->failedValidation($arguments);
                }
                $arguments = $arguments->validated();
            }
            $arguments = config()->get('soap.call.wrap_arguments_in_array', true) ? [$arguments] : $arguments;
            $result = $this->engine->request($method, $arguments);
            if ($result instanceof ResultProviderInterface) {
                return $this->buildResponse(Response::fromSoapResponse($result->getResult()));
            }
            if (! $result instanceof ResultInterface) {
                return $this->buildResponse(Response::fromSoapResponse($result));
            }

            return $this->buildResponse(new Response(new Psr7Response(200, [], $result)));
        } catch (\Exception $exception) {
            if ($exception instanceof \SoapFault) {
                return $this->buildResponse(Response::fromSoapFault($exception));
            }
            $previous = $exception->getPrevious();
            $this->dispatchConnectionFailedEvent();
            if ($previous instanceof HttpException) {
                /** @var HttpException $previous */
                return new Response($previous->getResponse());
            }

            throw SoapException::fromThrowable($exception);
        }
    }

    protected function buildResponse($response)
    {
        return tap($response, function ($result) {
            $this->populateResponse($result);
            $this->dispatchResponseReceivedEvent($result);
        });
    }

    /**
     * Build the Soap client.
     *
     * @param  string  $setup
     * @return SoapClient
     *
     * @throws NotFoundConfigurationException
     */
    public function buildClient(string $setup = '')
    {
        $this->byConfig($setup);
        $this->withGuzzleClientOptions([
            'handler' => $this->buildHandlerStack(),
            'on_stats' => function ($transferStats) {
                $this->transferStats = $transferStats;
            },
        ]);
        $this->isClientBuilded = true;

        return $this;
    }

    /**
     * @param  string  $setup
     * @return $this
     *
     * @throws NotFoundConfigurationException
     */
    public function byConfig(string $setup)
    {
        if (! empty($setup)) {
            $setup = config()->get('soap.clients.'.$setup);
            if (! $setup) {
                throw new NotFoundConfigurationException($setup);
            }
            foreach ($setup as $setupItem => $setupItemConfig) {
                if (is_bool($setupItemConfig)) {
                    $this->{Str::camel($setupItem)}();
                } elseif (is_array($setupItemConfig)) {
                    $this->{Str::camel($setupItem)}($this->arrayKeysToCamel($setupItemConfig));
                } elseif (is_string($setupItemConfig)) {
                    $this->{Str::camel($setupItem)}($setupItemConfig);
                }
            }
        }

        return $this;
    }

    /**
     * @param  array  $items
     * @return array
     */
    protected function arrayKeysToCamel(array $items)
    {
        $changedItems = [];
        foreach ($items as $key => $value) {
            $changedItems[Str::camel($key)] = $value;
        }

        return $changedItems;
    }

    /**
     * Build the before sending handler stack.
     *
     * @return \GuzzleHttp\HandlerStack
     */
    public function buildHandlerStack()
    {
        return tap(HandlerStack::create(), function ($stack) {
            $stack->push($this->buildBeforeSendingHandler(), 'before_sending');
            $stack->push($this->buildRecorderHandler(), 'recorder');
            $stack->push($this->buildStubHandler(), 'stub');
        });
    }

    /**
     * Build the before sending handler.
     */
    public function buildBeforeSendingHandler(): Closure
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                return $handler($this->runBeforeSendingCallbacks($request, $options), $options);
            };
        };
    }

    /**
     * Execute the "before sending" callbacks.
     */
    public function runBeforeSendingCallbacks(RequestInterface $request, array $options): mixed
    {
        return tap($request, function ($request) use ($options) {
            $this->beforeSendingCallbacks->each->__invoke(
                (new Request($request)),
                $options,
                $this
            );
        });
    }

    /**
     * Populate the given response with additional data.
     *
     * @param  \CodeDredd\Soap\Client\Response  $response
     * @return \CodeDredd\Soap\Client\Response
     */
    protected function populateResponse(Response $response)
    {
        $response->cookies = $this->cookies;

        $response->transferStats = $this->transferStats;

        return $response;
    }

    /**
     * Dispatch the RequestSending event if a dispatcher is available.
     *
     * @return void
     */
    protected function dispatchRequestSendingEvent()
    {
        event(new RequestSending($this->request));
    }

    /**
     * Dispatch the ResponseReceived event if a dispatcher is available.
     *
     * @param  \CodeDredd\Soap\Client\Response  $response
     * @return void
     */
    protected function dispatchResponseReceivedEvent(Response $response)
    {
        if (! $this->request) {
            return;
        }

        event(new ResponseReceived($this->request, $response));
    }

    /**
     * Dispatch the ConnectionFailed event if a dispatcher is available.
     *
     * @return void
     */
    protected function dispatchConnectionFailedEvent()
    {
        event(new ConnectionFailed($this->request));
    }

    /**
     * Build the recorder handler.
     *
     * @return Closure
     */
    public function buildRecorderHandler()
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                $promise = $handler($request, $options);

                return $promise->then(function ($response) use ($request) {
                    optional($this->factory)->recordRequestResponsePair(
                        (new Request($request)),
                        new Response($response)
                    );

                    return $response;
                });
            };
        };
    }

    /**
     * Build the stub handler.
     *
     * @return Closure
     */
    public function buildStubHandler()
    {
        return function (callable $handler) {
            return function ($request, $options) use ($handler) {
                $response = ($this->stubCallbacks ?? collect())
                    ->map
                    ->__invoke((new Request($request)), $options)
                    ->filter()
                    ->first();
                if (is_null($response)) {
                    return $handler($request, $options);
                } elseif (is_array($response)) {
                    return SoapFactory::response($response);
                }

                return $response;
            };
        };
    }

    /**
     * @return $this
     */
    protected function refreshEngine()
    {
        $this->refreshPluginClient();
        $this->setTransport();
        $this->refreshExtSoapOptions();
        $this->engine = ExtSoapEngineFactory::fromOptionsWithHandler(
            $this->extSoapOptions,
            $this->transport,
            $this->factory->isRecording()
        );
        $this->refreshWsdlProvider();

        return $this;
    }

    protected function refreshExtSoapOptions()
    {
        $this->extSoapOptions = ExtSoapOptions::defaults($this->wsdl, $this->options);
        if ($this->factory->isRecording()) {
            $this->wsdlProvider = new FlatteningLoader(new StreamWrapperLoader());
//            $this->extSoapOptions->withWsdlProvider($this->wsdlProvider);
        }
    }

    /**
     * @param  string  $wsdl
     * @return $this
     */
    public function baseWsdl(string $wsdl)
    {
        $this->wsdl = $wsdl;

        return $this;
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function stub($callback)
    {
        $this->stubCallbacks = collect($callback);

        return $this;
    }
}
