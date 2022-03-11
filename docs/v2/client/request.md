# Request

## Simple call
To make requests, you may use the `call` method or your soap action through magic `__call`. First, let's examine how to make a basic `action` request:

    use CodeDredd\Soap\Facades\Soap;

    $response = Soap::baseWsdl('http://test.com'/v1?wsdl)->call('Get_Users');
    // Or via magic method call
    $response = Soap::baseWsdl('http://test.com'/v1?wsdl)->Get_Users();

## Call with arguments

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
