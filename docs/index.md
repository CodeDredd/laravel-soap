# :fontawesome-solid-journal-whills: **Get started**

---
This package provides an expressive, minimal API around the [Soap Client from Phpro](https://github.com/phpro/soap-client), allowing you to quickly make outgoing SOAP requests to communicate with other web applications.

---
![alt text](https://www.netways.de/wp-content/uploads/2009/12/6a00d8341d3df553ef012875f312f9970c-800wi.jpg "Laravel Soap"){: style="height:auto;width:100%"}

---
## :fontawesome-brands-jedi-order: **Introduction**

It is using [HTTPplug](http://httplug.io/) as handler with [Guzzle](https://github.com/php-http/guzzle6-adapter) as client.
Some code is based/copied on/from [Laravel Http wrapper](https://github.com/illuminate/http). Thanks for inspiration :-)

## :fontawesome-brands-jedi-order: **Installation**

!!! abstract "Package"
    Execute the following command to get the latest version of the package:
    ````bash
    composer require codedredd/laravel-soap
    ````

!!! info "Configuration"
    Publish Configuration
    ```bash
    php artisan vendor:publish --provider "CodeDredd\Soap\SoapServiceProvider"
    ```

!!! warning "Code generation feature"
    If you also want to use the code generation feature you have to install following packages:
    ````bash
    composer require --dev laminas/laminas-code wsdl2phpgenerator/wsdl2phpgenerator
    ````
