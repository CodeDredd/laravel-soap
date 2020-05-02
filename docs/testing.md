# :fontawesome-solid-journal-whills: **Testing**

Many Laravel services provide functionality to help you easily and expressively write tests, and this SOAP wrapper is no exception.

## :fontawesome-brands-jedi-order: **Faking**

The `Soap` facade's `fake` method allows you to instruct the SOAP client to return stubbed / dummy responses when requests are made.

### Simple Fake
For example, to instruct the SOAP client to return empty, `200` status code responses for every request, you may call the `fake` method with no arguments:

    use CodeDredd\Soap\Facades\Soap;

    Soap::fake();

    $response = Soap::baseWsdl(...)->call(...);

### Faking Specific URLs

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

### Faking Response Sequences

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

### Fake Callback

If you require more complicated logic to determine what responses to return for certain endpoints, you may pass a callback to the `fake` method. This callback will receive an instance of `CodeDredd\Soap\Client\Request` and should return a response instance:

    Soap::fake(function ($request) {
        return Soap::response('Hello World', 200);
    });

------------------------

## :fontawesome-brands-jedi-order: **Asserts**

When faking responses, you may occasionally wish to inspect the requests the client receives in order to make sure your application is sending the correct data or headers.

### :fontawesome-solid-jedi: ***assertSent***

> Assert that a request / response pair was recorded matching a given truth test.

!!! info ""
    - **`Method`** : `#!php-inline function assertSent(callable $callback)`
    - **`Return`** : `#!php-inline void`

!!! example "Examples"
    === "simple"
        ``` php-inline
        Soap::assertSent(function($request){
            return $request->action() === 'YourAction'
        });
        ```
    === "with arguments"
        ``` php-inline
        Soap::assertSent(function($request){
            return $request->action() === 'YourAction' &&
                $request->arguments() === ['argument' => 'value']
        });
        ```
    === "full"
        ``` php-inline
        Soap::fake();
        
        Soap::baseWsdl('https://test/v1?wsdl')->call('YourAction', ['argument' => 'value']);
        
        Soap::assertSent(function($request){
            return $request->action() === 'YourAction' &&
                $request->arguments() === ['argument' => 'value']
        });
        ```
        
### :fontawesome-solid-jedi: ***assertNotSent***

> Assert that a request / response pair was not recorded matching a given truth test.

!!! info ""
    - **`Method`** : `#!php-inline function assertNotSent(callable $callback)`
    - **`Return`** : `#!php-inline void`

!!! example "Examples"
    === "simple"
        ``` php-inline
        Soap::assertNotSent(function($request){
            return $request->action() === 'YourAction'
        });
        ```
    === "with arguments"
        ``` php-inline
        Soap::assertNotSent(function($request){
            return $request->action() === 'YourAction' &&
                $request->arguments() === ['argument' => 'value']
        });
        ```
    === "full"
        ``` php-inline
        Soap::fake();
        
        Soap::baseWsdl('https://test/v1?wsdl')->call('YourAction', ['argument' => 'value']);
        
        Soap::assertNotSent(function($request){
            return $request->action() === 'YourAction' &&
                $request->arguments() === ['argument' => 'NotThisValue']
        });
        ```
        
### :fontawesome-solid-jedi: ***assertActionCalled***

> Assert that a given soap action is called with optional arguments.

!!! info ""
    - **`Method`** : `#!php-inline function assertActionCalled(string $action)`
    - **`Return`** : `#!php-inline void`

!!! example "Examples"
    === "simple"
        ``` php-inline
        Soap::assertActionCalled('YourAction');
        ```
    === "full"
        ``` php-inline
        Soap::fake();
        
        Soap::baseWsdl('https://test/v1?wsdl')->call('YourAction');
        
        Soap::assertActionCalled('YourAction');
        ```
### :fontawesome-solid-jedi: ***assertNothingSent***

> Assert that no request / response pair was recorded.

!!! info ""
    - **`Method`** : `#!php-inline function assertNothingSent()`
    - **`Return`** : `#!php-inline void`

!!! example "Examples"
    === "simple"
        ``` php-inline
        Soap::assertNothingSent();
        ```
    === "full"
        ``` php-inline
        Soap::fake();
        
        Soap::assertNothingSent();
        ```

### :fontawesome-solid-jedi: ***assertSequencesAreEmpty***

> Assert that every created response sequence is empty.

!!! info ""
    - **`Method`** : `#!php-inline function assertSequencesAreEmpty()`
    - **`Return`** : `#!php-inline void`

!!! example "Examples"
    === "simple"
        ``` php-inline
        Soap::assertSequencesAreEmpty();
        ```
    === "full"
        ``` php-inline
        Soap::fake();
        
        Soap::assertSequencesAreEmpty();
        ```
        
### :fontawesome-solid-jedi: ***assertSentCount***

> Assert how many requests have been recorded.

!!! info ""
    - **`Method`** : `#!php-inline function assertSentCount(int $count)`
    - **`Return`** : `#!php-inline void`

!!! example "Examples"
    === "simple"
        ``` php-inline
        Soap::assertSentCount(3);
        ```
    === "full"
        ``` php-inline
        Soap::fake();
        
        $client = Soap::buildClient('laravlel_soap');
        $response = $client->call('YourAction');
        $response2 = $client->call('YourOtherAction');
        
        Soap::assertSentCount(2);
        ```
