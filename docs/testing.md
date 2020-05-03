# :fontawesome-solid-journal-whills: **Testing**

---
Many Laravel services provide functionality to help you easily and expressively write tests, 
and this SOAP wrapper is no exception.

---
![alt text](https://image.slidesharecdn.com/agilelessonsfromstarwarsapil17-170125183657/95/agile-lessons-to-learn-from-star-wars-15-638.jpg?cb=1485369462 "Testing"){: style="height:auto;width:100%"}

---
## :fontawesome-brands-jedi-order: **Faking**

Include `#!php-inline use CodeDredd\Soap\Facades\Soap` in your testing class.
The `Soap` facade's `fake` method allows you to instruct the SOAP client to return stubbed / dummy responses 
when requests are made.

### :fontawesome-solid-jedi: ***fake***

> Intercepts request with possible given responses

!!! info ""
    - **`Method`** : `#!php-inline function fake($callback = null)`
    - **`Param`** : `#!php-inline callable|array $callback`
    - **`Return`** : `#!php-inline \CodeDredd\Soap\SoapFactory`

!!! example "Examples with Soap::response"
    === "simple"
        For returning empty `200` status code responses for every request, you may call the `fake` method with no arguments
        ``` php-inline
        Soap::fake();
        ```
    === "with arguments"
        You may pass an array to the `fake` method. The array's keys should represent ACTION patterns that you wish to fake and their associated responses. The `*` character may be used as a wildcard character. You may use the `response` method to construct stub / fake responses for these endpoints
        ```` php-inline
        Soap::fake([
            // Stub a JSON response for all Get_ actions...
            'Get_*' => Soap::response(['foo' => 'bar'], 200, ['Headers']),
    
            // Stub a string response for Submit_User action
            'Submit_User' => Soap::response('Hello World', 200, ['Headers']),
        ]);
        ````
        
        !!! warning "Difference between Laravel Http"
            The difference between Laravels HTTP wrapper is the fact that actions which are not defined in fake are also faked with a default 200 response!
            
    === "overwrite default response"
        ``` php-inline
        Soap::fake([
            // Stub a JSON response for all Get_ actions...
            'Get_*' => Soap::response(['foo' => 'bar'], 200, ['Headers']),
    
            // Stub a string response for all other actions
            '*' => Soap::response('Hello World', 200, ['Headers']),
        ]);
        ```
    === "with callback"
        If you require more complicated logic to determine what responses to return for certain endpoints, you may pass a callback to the `fake` method. This callback will receive an instance of `CodeDredd\Soap\Client\Request` and should return a response instance:
        ``` php-inline
        Soap::fake(function ($request) {
            return Soap::response('Hello World', 200);
        });
        ```

!!! example "Examples with Soap::sequence"
    === "simple"
        Sometimes you may need to specify that a single ACTION should return a series of fake responses in a specific order. You may accomplish this by using the `Soap::sequence` method to build the responses:
        ```` php-inline
        Soap::fake([
            // Stub a series of responses for Get_* actions...
            'Get_*' => Soap::sequence()
                ->push('Hello World')
                ->push(['foo' => 'bar'])
                ->pushStatus(404)
        ]);
        ````
        
        !!! warning "Throws exception if empty"
            When all of the responses in a response sequence have been consumed, any further requests will cause the response sequence to throw an exception!
            
    === "wtih whenEmpty"
        If you would like to specify a default response that should be returned when a sequence is empty, you may use the `whenEmpty` method
        ``` php-inline
        Soap::fake([
            // Stub a series of responses for Get_* actions...
            'Get_*' => Soap::sequence()
                ->push('Hello World')
                ->push(['foo' => 'bar'])
                ->whenEmpty(Soap::response())
        ]);
        ```

### :fontawesome-solid-jedi: ***response***

> Create a new response instance for use during stubbing (for fake responses)

!!! info ""

    - **`Method`** : `#!php-inline static function response($body = null, $status = 200, $headers = [])`
    - **`Param`** : `#!php-inline array|string|null $body`
    - **`Param`** : `#!php-inline int $status`
    - **`Param`** : `#!php-inline array $headers`
    - **`Return`** : `#!php-inline \GuzzleHttp\Promise\PromiseInterface`

!!! warning "When `$body` is string"
    One important notice. Because a SOAP API doesn't return a single string value every response with only a string in the body is wrapped in an array with key `response`.
    
        [
            'response' => 'Hello World'
        ]

### :fontawesome-solid-jedi: ***sequence***

> Get an invokable object that returns a sequence of responses in order for use during stubbing

!!! info ""
    - **`Method`** : `#!php-inline function sequence(array $responses = [])`
    - **`Return`** : `#!php-inline \CodeDredd\Soap\Client\ResponseSequence`
    
#### :fontawesome-brands-galactic-republic: ***push***

> Push a response to the sequence.

!!! info ""
    - **`Method`** : `#!php-inline function push($body = '', int $status = 200, array $headers = [])`
    - **`Return`** : `#!php-inline \CodeDredd\Soap\Client\ResponseSequence`

#### :fontawesome-brands-galactic-republic: ***pushResponse***

> Push a response to the sequence.

!!! info ""
    - **`Method`** : `#!php-inline function pushResponse($response)`
    - **`Param`** : `#!php-inline \GuzzleHttp\Promise\PromiseInterface|\Closure $response`
    - **`Return`** : `#!php-inline \CodeDredd\Soap\Client\ResponseSequence`
    
#### :fontawesome-brands-galactic-republic: ***pushStatus***

> Push a response with the given status code to the sequence.

!!! info ""
    - **`Method`** : `#!php-inline function pushStatus(string $filePath, int $status = 200, array $headers = [])`
    - **`Return`** : `#!php-inline \CodeDredd\Soap\Client\ResponseSequence`
    
#### :fontawesome-brands-galactic-republic: ***dontFailWhenEmpty***

> Make the sequence return a default response when it is empty.

!!! info ""
    - **`Method`** : `#!php-inline function dontFailWhenEmpty()`
    - **`Return`** : `#!php-inline \CodeDredd\Soap\Client\ResponseSequence`
    
#### :fontawesome-brands-galactic-republic: ***whenEmpty***

> Make the sequence return a custom default response when it is empty.

!!! info ""
    - **`Method`** : `#!php-inline function whenEmpty($response)`
    - **`Param`** : `#!php-inline \GuzzleHttp\Promise\PromiseInterface|\Closure $response`
    - **`Return`** : `#!php-inline \CodeDredd\Soap\Client\ResponseSequence`
    
#### :fontawesome-brands-galactic-republic: ***pushFile***

> Push response with the contents of a file as the body to the sequence.

!!! info ""
    - **`Method`** : `#!php-inline function pushFile(int $status = 200, array $headers = [])`
    - **`Return`** : `#!php-inline \CodeDredd\Soap\Client\ResponseSequence`

### :fontawesome-solid-jedi: ***fakeSequence***

If you would like to fake a sequence of responses but do not need to specify a specific ACTION pattern that should be faked, you may use the `Soap::fakeSequence` method.

> Register a response sequence for the given URL pattern.

!!! info ""
    - **`Method`** : `#!php-inline function fakeSequence(string $url = '*')`
    - **`Return`** : `#!php-inline \CodeDredd\Soap\Client\ResponseSequence`
    
!!! example "Example"
    ``` php-inline
    Soap::fakeSequence()
        ->push('Hello World')
        ->whenEmpty(Soap::response());
    ```
    
!!! tip "Tip"
    ``fakeSequence`` has the same methods as [`Soap::response`](#response).
    So in most cases `fakeSequence` will be the better choice to fake response because its an easier and shorter
    way to define fake responses.

---

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
