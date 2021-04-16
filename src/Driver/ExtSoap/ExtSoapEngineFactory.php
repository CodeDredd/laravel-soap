<?php

declare(strict_types=1);

namespace CodeDredd\Soap\Driver\ExtSoap;

use CodeDredd\Soap\Faker\EngineFaker;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Phpro\SoapClient\Soap\Engine\Engine;
use Phpro\SoapClient\Soap\Handler\HandlerInterface;

class ExtSoapEngineFactory
{
    public static function fromOptionsWithHandler(
        ExtSoapOptions $options,
        HandlerInterface $handler,
        $withMocking = false
    ) {
        $driver = ExtSoapDriver::createFromOptions($options);

        return $withMocking ? new EngineFaker($driver, $handler, $options) : new Engine($driver, $handler);
    }
}
