<?php

use RobRichards\XMLSecLibs\XMLSecurityKey;

return [
    /*
    |--------------------------------------------------------------------------
    | SOAP Code Generation directory
    |--------------------------------------------------------------------------
    |
    | Define the destination for the code generator under the app directory
    */

    'code' => [
        'path' => app_path('Soap'),
        'namespace' => 'App\\Soap',
    ],

    /*
    |--------------------------------------------------------------------------
    | SOAP Ray Configuration
    |--------------------------------------------------------------------------
    |
    | Define if all requests should go to ray
    */

    'ray' => [
        'send_soap_client_requests' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | SOAP Call behaviour
    |--------------------------------------------------------------------------
    |
    | Define if the arguments should be wrapped in an array
    */

    'call' => [
        'wrap_arguments_in_array' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | SOAP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Her you can setup your soap client by configuration so that ou just need
    | a name.
    |
    | example: Soap::buildClient('laravel_soap')
    */

    'clients' => [
        'laravel_soap' => [
            'base_wsdl' => 'test.wsdl',
            'with_wsa' => true,
            'with_basic_auth' => [
                'username' => 'username',
                'password' => 'password',
            ],
            'with_wsse' => [
                'user_token_name' => 'username',
                'user_token_password' => 'password',
                'private_key_file' => 'path/to/privatekey.pem',
                'public_key_file' => 'path/to/publickey.pyb',
                'server_certificate_file' => 'path/to/client-cert.pem',
                'server_certificate_has_subject_key_identifier' => false,
                'user_token_digest' => false,
                'digital_sign_method' => XMLSecurityKey::RSA_SHA1,
                'timestamp' => 3600,
                'sign_all_headers' => false,
            ],
        ],
    ],

];
