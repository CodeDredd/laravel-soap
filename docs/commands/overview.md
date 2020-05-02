# Overview of Commands

... better docs will come ;-)

## Requirements

You need to install following package to use the code generation feature

````bash
composer require --dev laminas/laminas-code wsdl2phpgenerator/wsdl2phpgenerator
````

Here is a list of all available soap commands.

## Commands

Command                                                  | Description
-------------------------------------------------------- | -------------
[`php artisan soap:make:client`](make-client.md)         | Create a customized client by wsdl or config name
[`php artisan soap:make:validation`](make-validation.md) | Create one or all validation classes by wsdl or config name

## Configuration

If you have published the configuration file then you have some options for the code generation.

Config             | Default            | Description
------------------ | ------------------ | -----------
``code.path``      | `app_path('Soap')` | Define where the generated Code should be saved in your project
``code.namespace`` | `App\\Soap`        | Define the namespace of the generated Code 
