# :fontawesome-solid-journal-whills: **Commands**

---
Yes this package comes with some artisan commands to make soap a bit more enjoyable.

---
![alt text](https://forum.endeavouros.com/uploads/default/original/2X/5/55b7271051f1bfcdcafcfd56e6704dade6936e1f.png "Artisan Commands"){: style="height:auto;width:100%"}

---
## :fontawesome-brands-jedi-order: **Requirements**

You need to install following package to use the code generation feature

````bash
composer require --dev laminas/laminas-code wsdl2phpgenerator/wsdl2phpgenerator
````

## :fontawesome-brands-jedi-order: **Overview**

Command                            | Description
---------------------------------- | -------------
`php artisan soap:make:client`     | Create a customized client by wsdl or config name
`php artisan soap:make:validation` | Create one or all validation classes by wsdl or config name

## :fontawesome-brands-jedi-order: **Configuration**

If you have published the configuration file then you have some options for the code generation.

Config             | Default            | Description
------------------ | ------------------ | -----------
``code.path``      | `app_path('Soap')` | Define where the generated Code should be saved in your project
``code.namespace`` | `App\\Soap`        | Define the namespace of the generated Code 
