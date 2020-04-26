<?php

namespace CodeDredd\Soap\Soap\Validations;

use Illuminate\Support\Facades\Validator;

class GetCustomersValidation
{
    public static function validator (array $parameters) {
        return Validator::make($parameters, [
            'Request_References' => 'array|filled',
            'Request_References.Customer_Reference.ID._' => 'string|required_with:Request_References',
            'Request_References.Customer_Reference.ID.type' => 'string',
        ]);
    }
}
