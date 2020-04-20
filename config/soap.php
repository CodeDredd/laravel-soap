<?php

return [

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
            'with_wsse' => [
                'user_token_name' => 'username',
                'user_token_password' => 'password',
            ]
        ],
	],

];