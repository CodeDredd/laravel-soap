# Installation

Execute the following command to get the latest version of the package:

    composer require codedredd/laravel-soap

Publish Configuration

    php artisan vendor:publish --provider "CodeDredd\Soap\SoapServiceProvider"

If you also want to use the code generation feature you have to install following packages:

````bash
composer require --dev laminas/laminas-code wsdl2phpgenerator/wsdl2phpgenerator
````
