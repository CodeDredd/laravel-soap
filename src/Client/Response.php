<?php

namespace CodeDredd\Soap\Client;

use ArrayAccess;
use CodeDredd\Soap\Exceptions\RequestException;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use LogicException;
use Phpro\SoapClient\Type\ResultInterface;
use function Psl\Type\string;
use Psr\Http\Message\ResponseInterface;
use VeeWee\Xml\Dom\Document;

/**
 * Class Response.
 */
class Response implements ResultInterface, ArrayAccess
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The underlying PSR response.
     */
    protected ResponseInterface $response;

    /**
     * The decoded JSON response.
     *
     * @var array
     */
    protected $decoded;

    /**
     * Create a new response instance.
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public static function fromSoapResponse(mixed $result, int $status = 200): Response
    {
        return new self(new Psr7Response($status, [], json_encode($result)));
    }

    public static function fromSoapFault(\SoapFault $soapFault): Response
    {
        return new self(new Psr7Response(400, [], $soapFault->getMessage()));
    }

    /**
     * Get the underlying PSR response for the response.
     */
    public function toPsrResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get the JSON decoded body of the response as an object.
     *
     * @return object
     */
    public function object(): object
    {
        return json_decode($this->body(), false);
    }

    /**
     * Get the JSON decoded body of the response as a collection.
     *
     * @param  string|null  $key
     * @return \Illuminate\Support\Collection
     */
    public function collect(string $key = null): Collection
    {
        return Collection::make($this->json($key));
    }

    /**
     * Get the body of the response.
     *
     * @param  bool  $transformXml
     * @param  bool  $sanitizeXmlFaultMessage
     * @return string
     */
    public function body(bool $transformXml = true, bool $sanitizeXmlFaultMessage = true): string
    {
        $body = (string) $this->response->getBody();
        if ($transformXml && Str::contains($body, '<?xml')) {
            $message = Document::fromXmlString($body)
                    ->xpath()
                    ->evaluate('string(.//faultstring)', string())
                ?? 'No Fault Message found';

            return trim($sanitizeXmlFaultMessage ? Str::after($message, 'Exception:') : $message);
        }

        return $body;
    }

    /**
     * Determine if the request was successful.
     *
     * @return bool
     */
    public function successful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Get the status code of the response.
     *
     * @return int
     */
    public function status(): int
    {
        return (int) $this->response->getStatusCode();
    }

    /**
     * Determine if the response code was "OK".
     */
    public function ok(): bool
    {
        return $this->status() === 200;
    }

    /**
     * Determine if the response indicates a client or server error occurred.
     */
    public function failed(): bool
    {
        return $this->serverError() || $this->clientError();
    }

    /**
     * Determine if the response was a redirect.
     */
    public function redirect(): bool
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
        $callback = func_get_args()[0] ?? null;

        if ($this->failed()) {
            throw tap(new RequestException($this), function ($exception) use ($callback) {
                if ($callback && is_callable($callback)) {
                    $callback($this, $exception);
                }
            });
        }

        return $this;
    }

    /**
     * Throw an exception if a server or client error occurred and the given condition evaluates to true.
     *
     * @param  bool  $condition
     * @return $this
     *
     * @throws \CodeDredd\Soap\Exceptions\RequestException
     */
    public function throwIf(bool $condition): Response|static
    {
        return $condition ? $this->throw() : $this;
    }

    /**
     * Determine if the response indicates a server error occurred.
     */
    public function serverError(): bool
    {
        return $this->status() >= 500;
    }

    /**
     * Determine if the response indicates a client error occurred.
     */
    public function clientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Execute the given callback if there was a server or client error.
     *
     * @param  \Closure|callable  $callback
     * @return \CodeDredd\Soap\Client\Response
     */
    public function onError(callable $callback)
    {
        if ($this->failed()) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Get the JSON decoded body of the response as an array.
     */
    public function json($key = null, $default = null): ?array
    {
        if (! $this->decoded) {
            $this->decoded = json_decode($this->body(), true);
        }

        if (is_null($key)) {
            return $this->decoded;
        }

        return data_get($this->decoded, $key, $default);
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->json()[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
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
    public function offsetSet($offset, $value): void
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
     * Get the body of the response.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->body();
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
