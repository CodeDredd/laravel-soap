<?php

namespace CodeDredd\Soap;

use CodeDredd\Soap\Client\Request;
use CodeDredd\Soap\Client\Response;
use CodeDredd\Soap\Driver\ExtSoap\ExtSoapEngineFactory;
use CodeDredd\Soap\Exceptions\NotFoundConfigurationException;
use CodeDredd\Soap\Exceptions\SoapException;
use CodeDredd\Soap\Middleware\CisDhlMiddleware;
use CodeDredd\Soap\Middleware\WsseMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Http\Client\Common\PluginClient;
use Http\Client\Exception\HttpException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Phpro\SoapClient\Type\ResultInterface;
use Phpro\SoapClient\Type\ResultProviderInterface;
use Psr\Http\Client\ClientInterface;
use Soap\Engine\Engine;
use Soap\Engine\Transport;
use Soap\ExtSoapEngine\AbusedClient;
use Soap\ExtSoapEngine\ExtSoapOptions;
use Soap\ExtSoapEngine\Transport\TraceableTransport;
use Soap\ExtSoapEngine\Wsdl\PassThroughWsdlProvider;
use Soap\ExtSoapEngine\Wsdl\WsdlProvider;
use Soap\Psr18Transport\Middleware\RemoveEmptyNodesMiddleware;
use Soap\Psr18Transport\Psr18Transport;
use Soap\Psr18Transport\Wsdl\Psr18Loader;
use Soap\Psr18WsseMiddleware\WsaMiddleware;
use Soap\Wsdl\Loader\FlatteningLoader;

/**
 * Class SoapClient.
 */
class SoapClient
{
    use Macroable {
        __call as macroCall;
    }

    protected ClientInterface $client;

    protected PluginClient $pluginClient;

    protected Engine $engine;

    protected array $options = [];

    protected ExtSoapOptions $extSoapOptions;

    protected TraceableTransport $transport;

    protected array $guzzleClientOptions = [];

    protected string $wsdl = '';

    protected bool $isClientBuilded = false;

    protected array $middlewares = [];

    protected SoapFactory|null $factory;

    protected FlatteningLoader|WsdlProvider $wsdlProvider;

    protected array $cookies;

    /**
     * The callbacks that should execute before the request is sent.
     */
    protected \Illuminate\Support\Collection $beforeSendingCallbacks;

    /**
     * The stub callables that will handle requests.
     */
    protected \Illuminate\Support\Collection|null $stubCallbacks;

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
        $this->beforeSendingCallbacks = collect([
            function (Request $request, array $options) {
                $this->cookies = Arr::wrap($options['cookies']);
            },
        ]);
    }

    public function refreshWsdlProvider()
    {
        $this->wsdlProvider = new FlatteningLoader(Psr18Loader::createForClient($this->pluginClient));

        return $this;
    }

    public function refreshPluginClient(): static
    {
//        if ($this->factory->isRecording()) {
//            $this->client = new \Http\Mock\Client(Psr17FactoryDiscovery::findResponseFactory());
//        }
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
     *
     * @param  array  $headers
     * @return $this
     */
    public function withHeaders(array $headers)
    {
        return $this->withGuzzleClientOptions(array_merge_recursive($this->options, [
            'headers' => $headers,
        ]));
    }

    public function getTransport(): TraceableTransport
    {
        return $this->transport;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param $options
     * @return $this
     */
    public function withGuzzleClientOptions($options)
    {
        $this->guzzleClientOptions = array_merge_recursive($this->guzzleClientOptions, $options);
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
     * @param $options
     * @return $this
     */
    public function withWsse($options)
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
     *
     * @return array
     */
    public function debugLastSoapRequest(): array
    {
        $lastRequestInfo = $this->engine->collectLastRequestInfo();

        return [
            'request' => [
                'headers' => trim($lastRequestInfo->getLastRequestHeaders()),
                'body' => XmlFormatter::format($lastRequestInfo->getLastRequest()),
            ],
            'response' => [
                'headers' => trim($lastRequestInfo->getLastResponseHeaders()),
                'body' => XmlFormatter::format($lastRequestInfo->getLastResponse()),
            ],
        ];
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

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->call($method, $parameters[0] ?? $parameters);
    }

    /**
     * @param  string  $method
     * @param  array|Validator  $arguments
     * @return Response
     */
    public function call(string $method, $arguments = []): Response
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
            $arguments = [$arguments];
            $result = $this->engine->request($method, $arguments);
            if ($result instanceof ResultProviderInterface) {
                $result = Response::fromSoapResponse($result->getResult());
            }
            if (! $result instanceof ResultInterface) {
                $result = Response::fromSoapResponse($result);
            }
        } catch (\Exception $exception) {
            if ($exception instanceof \SoapFault) {
                /** @var \SoapFault $exception */
                return Response::fromSoapFault($exception);
            }
            $previous = $exception->getPrevious();
            if ($previous instanceof HttpException) {
                /** @var HttpException $previous */
                return new Response($previous->getResponse());
            }
            throw SoapException::fromThrowable($exception);
        }

        return $result;
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
     *
     * @return \Closure
     */
    public function buildBeforeSendingHandler()
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                return $handler($this->runBeforeSendingCallbacks($request, $options), $options);
            };
        };
    }

    /**
     * Execute the "before sending" callbacks.
     *
     * @param  \GuzzleHttp\Psr7\RequestInterface  $request
     * @param  array  $options
     * @return \Closure
     */
    public function runBeforeSendingCallbacks($request, array $options)
    {
        return tap($request, function ($request) use ($options) {
            $this->beforeSendingCallbacks->each->__invoke(
                (new Request($request)),
                $options
            );
        });
    }

    /**
     * Build the recorder handler.
     *
     * @return \Closure
     */
    public function buildRecorderHandler()
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                $promise = $handler($this->runBeforeSendingCallbacks($request, $options), $options);

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
     * @return \Closure
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
            $this->wsdlProvider = new PassThroughWsdlProvider();
            $this->extSoapOptions->withWsdlProvider($this->wsdlProvider);
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
