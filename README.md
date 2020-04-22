# Laravel SOAP Client

[![Software License](https://img.shields.io/github/license/codedredd/laravel-soap?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dm/codedredd/laravel-soap?style=flat-square)](https://packagist.org/packages/codedredd/laravel-soap)
![test](https://img.shields.io/github/workflow/status/codedredd/laravel-soap/test?style=flat-square)
![version](https://img.shields.io/github/v/release/codedredd/laravel-soap?style=flat-square)

- [Installation](#installation)
- [Introduction](#introduction)
- [Making Requests](#making-requests)
    - [Request Data](#request-data)
    - [Headers](#headers)
    - [Authentication](#authentication)
    - [Error Handling](#error-handling)
    - [SOAP Options](#soap-options)
- [Testing](#testing)
    - [Faking Responses](#faking-responses)
    - [Inspecting Requests](#inspecting-requests)
- [Contributing](#contributing)
- [License](#license)

<a name="installation"></a>
## Installation

Execute the following command to get the latest version of the package:

    composer require codedredd/laravel-soap

Publish Configuration

    php artisan vendor:publish --provider "CodeDredd\Soap\SoapServiceProvider"

<a name="introduction"></a>
## Introduction

This package provides an expressive, minimal API around the [Soap Client from Phpro](https://github.com/phpro/soap-client), allowing you to quickly make outgoing SOAP requests to communicate with other web applications.
It is using [HTTPplug](http://httplug.io/) as handler with [Guzzle](https://github.com/php-http/guzzle6-adapter) as client.
Some code is based/copied on/from [Laravel Http wrapper](https://github.com/illuminate/http). Thanks for inspiration :-)

<a name="making-requests"></a>
## Making Requests

To make requests, you may use the `call` method or your soap action through magic `__call`. First, let's examine how to make a basic `action` request:

    use CodeDredd\Soap\Facades\Soap;

    $response = Soap::baseWsdl('http://test.com'/v1?wsdl)->call('Get_Users');
    // Or via magic method call
    $response = Soap::baseWsdl('http://test.com'/v1?wsdl)->Get_Users();

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
    
If you have published the config file then your able to setup a soap client in that configuration. After that it's even easier to initialize the client:

    $client = Soap::buildClient('your_client_name');
    $response = $client->call(...);

<a name="request-data"></a>
### Request Data

Of course, calling a action with arguments is also possible:

    $response = Soap::baseWsdl('http://test.com'/v1?wsdl)
        ->call('Submit_User', [
            'name' => 'Steve',
            'role' => 'Network Administrator',
        ]);
    // Or via magic method call
    $response = Soap::baseWsdl('http://test.com'/v1?wsdl)
        ->Submit_User([
            'name' => 'Steve',
            'role' => 'Network Administrator',
        ]);

<a name="headers"></a>
### Headers

Headers may be added to requests using the `withHeaders` method. This `withHeaders` method accepts an array of key / value pairs:

    $response = Soap::withHeaders([
        'X-First' => 'foo',
        'X-Second' => 'bar'
    ])->baseWsdl('http://test.com'/v1?wsdl)->call('Get_Users');

<a name="authentication"></a>
### Authentication

You may specify basic authentication credentials using the `withBasicAuth` method, respectively:

    // Basic authentication...
    $response = Soap::baseWsdl('http://test.com'/v1?wsdl)->withBasicAuth('taylor@laravel.com', 'secret')->call(...);

#### Web Service Security (WSS / WSSE)

Internally it is using the [wse-php package of robrichards](https://github.com/robrichards/wse-php) which is a well known library that is used by many developers.
It also supports not secured Wsse but with token:

    //Not secure
    $response = Soap::baseWsdl('http://test.com'/v1?wsdl)->withWsse([
        'userTokenName' => 'username',
        'userTokenPassword' => 'password',
    ])->call(...);
    //Secure
    $response = Soap::baseWsdl('http://test.com'/v1?wsdl)->withWsse([
        'privateKeyFile' => 'path/to/privatekey.pem',
        'publicKeyFile' => 'path/to/publickey.pyb',
    ])->call(...);

You have following Wsse Options:

    'userTokenName' : string
    'userTokenPassword' : string
    'privateKeyFile' : string
    'publicKeyFile' : string
    'serverCertificateFile' : string
    'serverCertificateHasSubjectKeyIdentifier' : boolean
    'userTokenDigest' : boolean
    'digitalSignMethod' : string
    'timestamp' : integer
    'signAllHeaders' => : boolean

#### Web Service Addressing (WSA)

Like Wss/Wsse it uses the same package:

    $response = Soap::baseWsdl(...)
        ->withWsa()
        ->call(...)

<a name="error-handling"></a>
### Error Handling

Unlike Guzzle's default behavior, this SOAP client wrapper does not throw exceptions on client or server errors (`400` and `500` level responses from servers). You may determine if one of these errors was returned using the `successful`, `clientError`, or `serverError` methods:

    // Determine if the status code was >= 200 and < 300...
    $response->successful();

    // Determine if the response has a 400 level status code...
    $response->clientError();

    // Determine if the response has a 500 level status code...
    $response->serverError();

#### Throwing Exceptions

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

<a name="soap-options"></a>
### Soap Client Options

You may specify additional [Soap request options](https://doc.bccnsoft.com/docs/php-docs-7-en/soapclient.soapclient.html) using the `withOptions` method. The `withOptions` method accepts an array of key / value pairs:

    $response = Soap::baseWsdl(...)->withOptions([
        'trace' => true,
    ])->call(...);

By default this options are set by the Phpro package:

    'trace' => true,
    'exceptions' => true,
    'keep_alive' => true,
    'cache_wsdl' => WSDL_CACHE_DISK, // Avoid memory cache: this causes SegFaults from time to time.
    'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
    'typemap' => new TypeConverterCollection([
        new TypeConverter\DateTimeTypeConverter(),
        new TypeConverter\DateTypeConverter(),
        new TypeConverter\DecimalTypeConverter(),
        new TypeConverter\DoubleTypeConverter()
    ]),

<a name="testing"></a>
## Testing

Many Laravel services provide functionality to help you easily and expressively write tests, and this SOAP wrapper is no exception. The `Soap` facade's `fake` method allows you to instruct the SOAP client to return stubbed / dummy responses when requests are made.

<a name="faking-responses"></a>
### Faking Responses

For example, to instruct the SOAP client to return empty, `200` status code responses for every request, you may call the `fake` method with no arguments:

    use CodeDredd\Soap\Facades\Soap;

    Soap::fake();

    $response = Soap::baseWsdl(...)->call(...);

#### Faking Specific URLs

Alternatively, you may pass an array to the `fake` method. The array's keys should represent ACTION patterns that you wish to fake and their associated responses. The `*` character may be used as a wildcard character. You may use the `response` method to construct stub / fake responses for these endpoints:
The difference between Laravels HTTP wrapper is the fact that actions which are not defined in fake are also faked with a default 200 response!
Also a faked response status code is always 200 if you define it in the range between 200 and 400. Status codes 400 and greater are correct responded.

    Soap::fake([
        // Stub a JSON response for all Get_ actions...
        'Get_*' => Soap::response(['foo' => 'bar'], 200, ['Headers']),

        // Stub a string response for Submit_User action
        'Submit_User' => Soap::response('Hello World', 200, ['Headers']),
    ]);

If you would like to overwrite the fallback ACTION pattern that will stub all unmatched URLs, you may use a single `*` character:

    Soap::fake([
        // Stub a JSON response for all Get_ actions...
        'Get_*' => Soap::response(['foo' => 'bar'], 200, ['Headers']),

        // Stub a string response for all other actions
        '*' => Soap::response('Hello World', 200, ['Headers']),
    ]);

One important notice. Because a SOAP API doesn't return only string every response with only a string in the body will be formatted to an array:

    //For above example
    [
        'response' => 'Hello World'
    ]

#### Faking Response Sequences

Sometimes you may need to specify that a single ACTION should return a series of fake responses in a specific order. You may accomplish this by using the `Soap::sequence` method to build the responses:

    Soap::fake([
        // Stub a series of responses for Get_* actions...
        'Get_*' => Soap::sequence()
            ->push('Hello World')
            ->push(['foo' => 'bar'])
            ->pushStatus(404)
    ]);

When all of the responses in a response sequence have been consumed, any further requests will cause the response sequence to throw an exception. If you would like to specify a default response that should be returned when a sequence is empty, you may use the `whenEmpty` method:

    Soap::fake([
        // Stub a series of responses for Get_* actions...
        'Get_*' => Soap::sequence()
            ->push('Hello World')
            ->push(['foo' => 'bar'])
            ->whenEmpty(Soap::response())
    ]);

If you would like to fake a sequence of responses but do not need to specify a specific ACTION pattern that should be faked, you may use the `Soap::fakeSequence` method:

    Soap::fakeSequence()
        ->push('Hello World')
        ->whenEmpty(Soap::response());

#### Fake Callback

If you require more complicated logic to determine what responses to return for certain endpoints, you may pass a callback to the `fake` method. This callback will receive an instance of `CodeDredd\Soap\Client\Request` and should return a response instance:

    Soap::fake(function ($request) {
        return Soap::response('Hello World', 200);
    });

<a name="inspecting-requests"></a>
### Inspecting Requests

When faking responses, you may occasionally wish to inspect the requests the client receives in order to make sure your application is sending the correct data or headers. You may accomplish this by calling the `Soap::assertSent` method after calling `Soap::fake`.

The `assertSent` method accepts a callback which will be given an `CodeDredd\Soap\Client\Request` instance and should return a boolean value indicating if the request matches your expectations. In order for the test to pass, at least one request must have been issued matching the given expectations:

    Soap::fake();

    Soap::withHeaders([
        'X-First' => 'foo',
    ])->baseWsdl('http://test.com/v1?wsdl')
    ->call('Get_Users', [
        'name' => 'CodeDredd'
    ]);

    Soap::assertSent(function ($request) {
        return $request->action() === 'Get_Users' && 
            $request->arguments() === ['name' => 'CodeDredd'];
    });
    //Or shortcut
    Soap::assertActionSent('Get_Users')

If needed, you may assert that a specific request was not sent using the `assertNotSent` method:

    Soap::fake();

    Soap::baseWsdl('http://test.com/v1?wsdl')
        ->call('Get_Users');

    Soap::assertNotSent(function (Request $request) {
        return $request->action() === 'Get_Posts';
    });

Or, if you would like to assert that no requests were sent, you may use the `assertNothingSent` method:

    Soap::fake();

    Soap::assertNothingSent();

<a name="contributing"></a>
## Contributing
Please post issues and send PRs.

<a name="licence"></a>
## License
Laravel Soap is open-sourced software licensed under the MIT license.
