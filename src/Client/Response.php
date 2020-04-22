<?php

namespace CodeDredd\Soap\Client;

use LogicException;
use ArrayAccess;
use CodeDredd\Soap\Exceptions\RequestException;
use CodeDredd\Soap\Xml\SoapXml;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Phpro\SoapClient\Type\ResultInterface;

/**
 * Class Response
 * @package CodeDredd\Soap\Client
 */
class Response implements ResultInterface, ArrayAccess
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The underlying PSR response.
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * The decoded JSON response.
     *
     * @var array
     */
    protected $decoded;

    /**
     * Create a new response instance.
     *
     * @param  \Psr\Http\Message\MessageInterface  $response
     * @return void
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * @param $result
     * @return Response
     */
    public static function fromSoapResponse($result)
    {
        return new self(new Psr7Response(200, [], json_encode($result)));
    }

    /**
     * @param  \SoapFault  $soapFault
     * @return Response
     */
    public static function fromSoapFault(\SoapFault $soapFault)
    {
        return new self(new Psr7Response(400, [], $soapFault->getMessage()));
    }

    /**
     * Get the full SOAP enveloppe response
     *
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * Get the JSON decoded body of the response as an object.
     *
     * @return object
     */
    public function object()
    {
        return json_decode($this->body(), false);
    }

    /**
     * Get the body of the response.
     *
     * @param  bool  $transformXml
     * @param  bool  $sanitizeXmlFaultMessage
     * @return string
     */
    public function body($transformXml = true, $sanitizeXmlFaultMessage = true)
    {
        $body = (string) $this->response->getBody();
        if ($transformXml && Str::contains($body, '<?xml')) {
            $message = SoapXml::fromString($body)->getFaultMessage();
            return trim($sanitizeXmlFaultMessage ? Str::after($message, 'Exception:') : $message);
        }
        return $body;
    }

    /**
     * Determine if the request was successful.
     *
     * @return bool
     */
    public function successful()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Get the status code of the response.
     *
     * @return int
     */
    public function status()
    {
        return (int) $this->response->getStatusCode();
    }

    /**
     * Determine if the response code was "OK".
     *
     * @return bool
     */
    public function ok()
    {
        return $this->status() === 200;
    }

    /**
     * Determine if the response was a redirect.
     *
     * @return bool
     */
    public function redirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * Throw an exception if a server or client error occurred.
     *
     * @return $this
     *
     * @throws \CodeDredd\Soap\Exceptions\RequestException
     */
    public function throw()
    {
        if ($this->serverError() || $this->clientError()) {
            throw new RequestException($this);
        }

        return $this;
    }

    /**
     * Determine if the response indicates a server error occurred.
     *
     * @return bool
     */
    public function serverError()
    {
        return $this->status() >= 500;
    }

    /**
     * Determine if the response indicates a client error occurred.
     *
     * @return bool
     */
    public function clientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->json()[$offset]);
    }

    /**
     * Get the JSON decoded body of the response as an array.
     *
     * @return array
     */
    public function json()
    {
        if (!$this->decoded) {
            $this->decoded = json_decode($this->body(), true);
        }

        return $this->decoded;
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->json()[$offset];
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     *
     * @throws \LogicException
     */
    public function offsetSet($offset, $value)
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     *
     * @throws \LogicException
     */
    public function offsetUnset($offset)
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    /**
     * Dynamically proxy other methods to the underlying response.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return static::hasMacro($method)
            ? $this->macroCall($method, $parameters)
            : $this->response->{$method}(...$parameters);
    }
}
