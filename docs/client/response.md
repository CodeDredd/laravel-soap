# Response

## Object

The `call` method returns an instance of `CodeDredd\Soap\Client\Response`, which provides a variety of methods that may be used to inspect the response:

    $response->body() : string;
    $response->json() : array;
    $response->status() : int;
    $response->ok() : bool;
    $response->successful() : bool;
    $response->serverError() : bool;
    $response->clientError() : bool;

The `CodeDredd\Soap\Client\Response` object also implements the PHP `ArrayAccess` interface, allowing you to access your response data directly on the response:

    return Soap::baseWsdl('http://test.com'/v1?wsdl)->call('Get_Users')['name'];

## Error Handling

Unlike Guzzle's default behavior, this SOAP client wrapper does not throw exceptions on client or server errors (`400` and `500` level responses from servers). You may determine if one of these errors was returned using the `successful`, `clientError`, or `serverError` methods:

    // Determine if the status code was >= 200 and < 300...
    $response->successful();

    // Determine if the response has a 400 level status code...
    $response->clientError();

    // Determine if the response has a 500 level status code...
    $response->serverError();

### Throwing Exceptions

If you have a response instance and would like to throw an instance of `CodeDredd\Soap\Exceptions\RequestException` if the response is a client or server error, you may use the `throw` method:

    $response = Soap::baseWsdl(...)->call(...);

    // Throw an exception if a client or server error occurred...
    $response->throw();

    return $response['user']['id'];

The `CodeDredd\Soap\Exceptions\RequestException` instance has a public `$response` property which will allow you to inspect the returned response.

The `throw` method returns the response instance if no error occurred, allowing you to chain other operations onto the `throw` method:

    return Soap::baseWsdl(...)
        ->call(...)
        ->throw()
        ->json();
