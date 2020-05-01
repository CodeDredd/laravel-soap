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
