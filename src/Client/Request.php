<?php

namespace CodeDredd\Soap\Client;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use LogicException;
use Psr\Http\Message\RequestInterface;
use Soap\Psr18Transport\HttpBinding\SoapActionDetector;
use Soap\Xml\Locator\SoapBodyLocator;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Dom\Traverser\Visitor\RemoveNamespaces;
use VeeWee\Xml\Encoding\Exception\EncodingException;

use function VeeWee\Xml\Dom\Configurator\traverse;
use function VeeWee\Xml\Encoding\element_decode;

/**
 * Class Request.
 */
class Request
{
    use Macroable;

    /**
     * The underlying PSR request.
     */
    protected RequestInterface $request;

    /**
     * The decoded payload for the request.
     */
    protected array $data;

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
     * Get the URL of the request.
     */
    public function url(): string
    {
        return (string) $this->request->getUri();
    }

    /**
     * Determine if the request has a given header.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function hasHeader($key, $value = null)
    {
        if (is_null($value)) {
            return ! empty($this->request->getHeaders()[$key]);
        }

        $headers = $this->headers();

        if (! Arr::has($headers, $key)) {
            return false;
        }

        $value = is_array($value) ? $value : [$value];

        return empty(array_diff($value, $headers[$key]));
    }

    /**
     * Determine if the request has the given headers.
     *
     * @param  array|string  $headers
     * @return bool
     */
    public function hasHeaders($headers)
    {
        if (is_string($headers)) {
            $headers = [$headers => null];
        }

        foreach ($headers as $key => $value) {
            if (! $this->hasHeader($key, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the values for the header with the given name.
     *
     * @param  string  $key
     * @return array
     */
    public function header($key)
    {
        return Arr::get($this->headers(), $key, []);
    }

    /**
     * Get the request headers.
     *
     * @return array
     */
    public function headers()
    {
        return $this->request->getHeaders();
    }

    /**
     * Get the body of the request.
     *
     * @return string
     */
    public function body()
    {
        return (string) $this->request->getBody();
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
        $doc = Document::fromXmlString($this->body());
        $wrappedArguments = config()->get('soap.call.wrap_arguments_in_array', true);
        $method = $doc->locate(new SoapBodyLocator());

        if ($wrappedArguments) {
            $method = $method?->firstElementChild;
        }

        return Arr::wrap(Arr::get(element_decode($method, traverse(new RemoveNamespaces())), $wrappedArguments ? 'node' : 'Body', []));
    }

    /**
     * Set the decoded data on the request.
     *
     * @param  array  $data
     * @return $this
     */
    public function withData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the underlying PSR compliant request instance.
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    public function toPsrRequest()
    {
        return $this->request;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     *
     * @throws EncodingException
     */
    public function offsetExists(string $offset): bool
    {
        return isset($this->arguments()[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     * @return mixed
     *
     * @throws EncodingException
     */
    public function offsetGet($offset): mixed
    {
        return $this->arguments()[$offset];
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     *
     * @throws LogicException
     */
    public function offsetSet($offset, $value): void
    {
        throw new LogicException('Request data may not be mutated using array access.');
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     *
     * @throws LogicException
     */
    public function offsetUnset($offset): void
    {
        throw new LogicException('Request data may not be mutated using array access.');
    }
}
