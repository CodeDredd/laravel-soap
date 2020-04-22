<?php

namespace CodeDredd\Soap;

use Closure;
use CodeDredd\Soap\Client\Request;
use CodeDredd\Soap\Client\ResponseSequence;
use CodeDredd\Soap\Xml\XMLSerializer;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;
use function GuzzleHttp\Promise\promise_for;

class SoapFactory
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The stub callables that will handle requests.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $stubCallbacks;

    /**
     * Indicates if the factory is recording requests and responses.
     *
     * @var bool
     */
    protected $recording = false;

    /**
     * The recorded response array.
     *
     * @var array
     */
    protected $recorded = [];

    /**
     * All created response sequences.
     *
     * @var array
     */
    protected $responseSequences = [];

    protected $fakeWsdlLocation;

    /**
     * Create a new factory instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->stubCallbacks = collect();
        $this->fakeWsdlLocation = __DIR__.'/Faker/fake.wsdl';
    }

    /**
     * Execute a method against a new pending request instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return tap(new SoapClient($this), function ($request) {
            $request->stub($this->stubCallbacks);
        })->{$method}(...$parameters);
    }

    public function isRecording()
    {
        return $this->recording;
    }

    /**
     * Record a request response pair.
     *
     * @param  \CodeDredd\Soap\Client\Request  $request
     * @param  \CodeDredd\Soap\Client\Response  $response
     * @return void
     */
    public function recordRequestResponsePair($request, $response)
    {
        if ($this->recording) {
            $this->recorded[] = [$request, $response];
        }
    }

    public function fakeWsdl(string $wsdl)
    {
        $this->fakeWsdlLocation = $wsdl;
        return $this;
    }

    public function getFakeWsdl()
    {
        return $this->fakeWsdlLocation;
    }

    /**
     * Register a response sequence for the given URL pattern.
     *
     * @param  string  $url
     * @return \CodeDredd\Soap\Client\ResponseSequence
     */
    public function fakeSequence($url = '*')
    {
        return tap($this->sequence(), function ($sequence) use ($url) {
            $this->fake([$url => $sequence]);
        });
    }

    /**
     * Get an invokable object that returns a sequence of responses in order for use during stubbing.
     *
     * @param  array  $responses
     * @return \CodeDredd\Soap\Client\ResponseSequence
     */
    public function sequence(array $responses = [])
    {
        return $this->responseSequences[] = new ResponseSequence($responses);
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param  callable|array  $callback
     * @return $this
     */
    public function fake($callback = null)
    {
        $this->record();

        if (is_null($callback)) {
            $callback = function () {
                return static::response();
            };
        }

        if (is_array($callback)) {
            $callback['*'] = $callback['*'] ?? self::response();
            foreach ($callback as $method => $callable) {
                $this->stubMethod($method, $callable);
            }
            return $this;
        }
        $this->stubCallbacks = $this->stubCallbacks->merge(collect([
            $callback instanceof Closure
                ? $callback
                : function () use ($callback) {
                return $callback;
            },
        ]));

        return $this;
    }

    /**
     * Begin recording request / response pairs.
     *
     * @return $this
     */
    protected function record()
    {
        $this->recording = true;

        return $this;
    }

    /**
     * Create a new response instance for use during stubbing.
     *
     * @param  array|string|null  $body
     * @param  int  $status
     * @param  array  $headers
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public static function response($body = null, $status = 200, $headers = [])
    {
        if (is_array($body)) {
            $body = json_encode($body);
        } elseif (is_string($body)) {
            $body = json_encode([
                'response' => $body
            ]);
        }
        return promise_for(new Psr7Response($status, $headers, $body));
    }

    /**
     * Stub the given URL using the given callback.
     *
     * @param  string  $method
     * @param  \CodeDredd\Soap\Client\Response|\GuzzleHttp\Promise\PromiseInterface|callable  $callback
     * @return $this
     */
    public function stubMethod($method, $callback)
    {
        return $this->fake(function ($request, $options) use ($method, $callback) {
            if (!Str::is(Str::start($method, '*'), $request->action())) {
                return;
            }
            return $callback instanceof Closure || $callback instanceof ResponseSequence
                ? $callback($request, $options)
                : $callback;
        });
    }

    /**
     * Assert that a request / response pair was recorded matching a given truth test.
     *
     * @param  string  $action
     * @return void
     */
    public function assertActionCalled(string $action)
    {
        $this->assertSent(function (Request $request) use ($action) {
            return $request->action() === $action;
        });
    }

    /**
     * Assert that a request / response pair was recorded matching a given truth test.
     *
     * @param  callable  $callback
     * @return void
     */
    public function assertSent($callback)
    {
        PHPUnit::assertTrue(
            $this->recorded($callback)->count() > 0,
            'An expected request was not recorded.'
        );
    }

    /**
     * Get a collection of the request / response pairs matching the given truth test.
     *
     * @param  callable  $callback
     * @return \Illuminate\Support\Collection
     */
    public function recorded($callback)
    {
        if (empty($this->recorded)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return collect($this->recorded)->filter(function ($pair) use ($callback) {
            return $callback($pair[0], $pair[1]);
        });
    }

    /**
     * Assert that a request / response pair was not recorded matching a given truth test.
     *
     * @param  callable  $callback
     * @return void
     */
    public function assertNotSent($callback)
    {
        PHPUnit::assertFalse(
            $this->recorded($callback)->count() > 0,
            'Unexpected request was recorded.'
        );
    }

    /**
     * Assert that no request / response pair was recorded.
     *
     * @return void
     */
    public function assertNothingSent()
    {
        PHPUnit::assertEmpty(
            $this->recorded,
            'Requests were recorded.'
        );
    }

    /**
     * Assert that every created response sequence is empty.
     *
     * @return void
     */
    public function assertSequencesAreEmpty()
    {
        foreach ($this->responseSequences as $responseSequence) {
            PHPUnit::assertTrue(
                $responseSequence->isEmpty(),
                'Not all response sequences are empty.'
            );
        }
    }
}
