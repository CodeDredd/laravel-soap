<?php

namespace CodeDredd\Soap;

use CodeDredd\Soap\Client\Request;
use CodeDredd\Soap\Client\Response;
use CodeDredd\Soap\Driver\ExtSoap\ExtSoapEngineFactory;
use CodeDredd\Soap\Exceptions\NotFoundConfigurationException;
use CodeDredd\Soap\Exceptions\SoapException;
use CodeDredd\Soap\Middleware\WsseMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Http\Client\Exception\HttpException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Phpro\SoapClient\Middleware\BasicAuthMiddleware;
use Phpro\SoapClient\Middleware\RemoveEmptyNodesMiddleware;
use Phpro\SoapClient\Middleware\WsaMiddleware;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Phpro\SoapClient\Soap\Engine\EngineInterface;
use Phpro\SoapClient\Soap\Handler\HandlerInterface;
use Phpro\SoapClient\Soap\Handler\HttPlugHandle;
use Phpro\SoapClient\Type\ResultInterface;
use Phpro\SoapClient\Type\ResultProviderInterface;
use Phpro\SoapClient\Util\XmlFormatter;
use Phpro\SoapClient\Wsdl\Provider\LocalWsdlProvider;
use Phpro\SoapClient\Wsdl\Provider\WsdlProviderInterface;

/**
 * Class SoapClient.
 */
class SoapClient
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * @var EngineInterface
     */
    protected $engine;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var ExtSoapOptions
     */
    protected $extSoapOptions;

    /**
     * @var
     */
    protected $handler;

    /**
     * @var array
     */
    protected $handlerOptions = [];

    /**
     * @var string
     */
    protected $wsdl = '';

    /**
     * @var bool
     */
    protected $isClientBuilded = false;

    /**
     * @var array
     */
    protected $middlewares = [];

    /**
     * @var SoapFactory|null
     */
    protected $factory;

    /**
     * @var WsdlProviderInterface
     */
    protected $wsdlProvider;

    /**
     * The request cookies.
     *
     * @var array
     */
    protected $cookies;

    /**
     * The callbacks that should execute before the request is sent.
     *
     * @var array
     */
    protected $beforeSendingCallbacks;

    /**
     * The stub callables that will handle requests.
     *
     * @var \Illuminate\Support\Collection|null
     */
    protected $stubCallbacks;

    /**
     * Create a new Soap Client instance.
     *
     * @param  \CodeDredd\Soap\SoapFactory|null  $factory
     * @return void
     */
    public function __construct(SoapFactory $factory = null)
    {
        $this->factory = $factory;
        $this->setHandler();
        $this->wsdlProvider = LocalWsdlProvider::create();
        $this->beforeSendingCallbacks = collect([
            function (Request $request, array $options) {
                $this->cookies = $options['cookies'];
            },
        ]);
    }

    /**
     * @param  HandlerInterface|null  $handler
     * @return $this
     */
    protected function setHandler(HandlerInterface $handler = null)
    {
        $this->handler = $handler ?? HttPlugHandle::createForClient(
                new Client($this->handlerOptions)
            );
        $this->addMiddleware();

        return $this;
    }

    /**
     * Adds middleware to the handler.
     */
    protected function addMiddleware()
    {
        foreach ($this->middlewares as $middleware) {
            $this->handler->addMiddleware($middleware);
        }
    }

    /**
     * Add the given headers to the request.
     *
     * @param  array  $headers
     * @return $this
     */
    public function withHeaders(array $headers)
    {
        return $this->withHandlerOptions(array_merge_recursive($this->options, [
            'headers' => $headers,
        ]));
    }

    /**
     * @param $options
     * @return $this
     */
    public function withHandlerOptions($options)
    {
        $this->handlerOptions = array_merge_recursive($this->handlerOptions, $options);

        return $this->setHandler();
    }

    /**
     * @return EngineInterface
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return $this
     */
    public function withRemoveEmptyNodes()
    {
        $this->middlewares = array_merge_recursive($this->middlewares, [
            'empty_nodes' => new RemoveEmptyNodesMiddleware(),
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

        $this->middlewares = array_merge_recursive($this->middlewares, [
            'basic' => new BasicAuthMiddleware($username, $password),
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function withWsa()
    {
        $this->middlewares = array_merge_recursive($this->middlewares, [
            'wsa' => new WsaMiddleware(),
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
            'wsse' => new WsseMiddleware($options),
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

        return $this->call($method, $parameters);
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
            if ($arguments instanceof Validator) {
                if ($arguments->fails()) {
                    return $this->failedValidation($arguments);
                }
                $arguments = $arguments->validated();
            }
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
     * @throws NotFoundConfigurationException
     */
    public function buildClient(string $setup = '')
    {
        $this->byConfig($setup);
        $this->withHandlerOptions([
            'handler' => $this->buildHandlerStack(),
        ]);
        $this->refreshEngine();
        $this->isClientBuilded = true;

        return $this;
    }

    /**
     * @param  string  $setup
     * @return $this
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
        $this->refreshExtSoapOptions();
        $this->engine = ExtSoapEngineFactory::fromOptionsWithHandler(
            $this->extSoapOptions,
            $this->handler,
            $this->factory->isRecording()
        );

        return $this;
    }

    protected function refreshExtSoapOptions()
    {
        if ($this->factory->isRecording()) {
            $this->baseWsdl($this->factory->getFakeWsdl());
        }
        $this->extSoapOptions = ExtSoapOptions::defaults($this->wsdl, $this->options);
        if ($this->factory->isRecording()) {
            $this->wsdlProvider->provide($this->factory->getFakeWsdl());
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
