# Authentication

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

You can also use the 2005 version of Wsa with 

    $response = Soap::baseWsdl(...)
        ->withWsa2005()
        ->call(...)

### DHL Cis Authentication

DHL uses his own authentication header

    $client = Soap::withCisDHLAuth('user', 'signature')
