<?php

namespace CodeDredd\Soap\Soap\Clients;

use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\Soap\Validations\GetCustomersValidation;
use Illuminate\Support\Traits\Macroable;

class LaravelSoapClient
{
    use Macroable {
        __call as macroCall;
    }

    protected $client;

    public function __construct()
    {
        $this->client = Soap::buildClient('laravel_soap');
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->client->call($method, $parameters);
    }

    public function Get_Customers(array $body = []) {
        $validation = GetCustomersValidation::validator($body);
        return $this->client->call('Get_Customers', $validation);
    }
}
