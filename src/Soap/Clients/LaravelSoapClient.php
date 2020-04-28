<?php

namespace CodeDredd\Soap\Soap\Clients;

use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\Soap\Contracts\LaravelSoapContract;
use CodeDredd\Soap\Soap\Validations\GetCustomersValidation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

/**
 * Class LaravelSoapClient
 * @method \CodeDredd\Soap\Client\Response Get_Customers($body = []) Blub
 * @package CodeDredd\Soap\Soap\Clients
 */
class LaravelSoapClient implements LaravelSoapContract
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * @var \CodeDredd\Soap\SoapClient
     */
    protected $client;

    /**
     * LaravelSoapClient constructor.
     */
    public function __construct()
    {
        $this->client = Soap::buildClient('laravel_soap');
    }

    /**
     * @param $method
     * @param $parameters
     * @return \CodeDredd\Soap\Client\Response|mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }
        $validationClass = 'CodeDredd\\Soap\\Soap\\Validations\\LaravelSoap\\'
            . ucfirst(Str::camel($method))
            . 'Validation';
        if (class_exists($validationClass)) {
            $parameters = app()->call([$validationClass, 'validator'], ['parameters' => $parameters]);
        }

        return $this->client->__call($method, $parameters);
    }

    public function getClient() {
        return $this->client;
    }
}
