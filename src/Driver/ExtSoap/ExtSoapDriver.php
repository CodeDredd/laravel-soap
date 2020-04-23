<?php

namespace CodeDredd\Soap\Driver\ExtSoap;

use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapDriver as PhproExtSoapDriver;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;

class ExtSoapDriver extends PhproExtSoapDriver
{
    public static function createFromOptions(ExtSoapOptions $options): PhproExtSoapDriver
    {
        $client = AbusedClient::createFromOptions($options);

        return self::createFromClient($client);
    }
}
