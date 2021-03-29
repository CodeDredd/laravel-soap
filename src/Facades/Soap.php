<?php

namespace CodeDredd\Soap\Facades;

use CodeDredd\Soap\SoapFactory;
use Illuminate\Support\Facades\Facade;

/**
 * Class Soap.
 *
 * @method static \CodeDredd\Soap\SoapClient baseWsdl(string $wsdl)
 * @method static \CodeDredd\Soap\SoapClient stub(callable $callback)
 * @method static \CodeDredd\Soap\SoapClient buildClient(string $setup = '')
 * @method static \CodeDredd\Soap\SoapClient byConfig(string $setup = '')
 * @method static \CodeDredd\Soap\SoapClient withOptions(array $options)
 * @method static \CodeDredd\Soap\SoapClient withHeaders(array $options)
 * @method static \CodeDredd\Soap\SoapClient handlerOptions(array $options)
 * @method static \CodeDredd\Soap\SoapClient withWsse(array $config)
 * @method static \CodeDredd\Soap\SoapClient withWsa()
 * @method static \CodeDredd\Soap\SoapClient withRemoveEmptyNodes()
 * @method static \CodeDredd\Soap\SoapClient withBasicAuth(string $username, string $password)
 * @method \CodeDredd\Soap\Client\Response call(string $method, array $arguments = [])
 * @method static \GuzzleHttp\Promise\PromiseInterface response($body = null, $status = 200, array $headers = [])
 * @method static \CodeDredd\Soap\Client\ResponseSequence sequence(array $responses = [])
 * @method static \CodeDredd\Soap\Client\ResponseSequence fakeSequence(string $urlPattern = '*')
 * @method static \CodeDredd\Soap\SoapFactory fake($callback = null)
 * @method static assertSent(callable $callback)
 * @method static assertNotSent(callable $callback)
 * @method static assertActionCalled(string $action)
 * @method static assertNothingSent()
 * @method static assertSequencesAreEmpty()
 * @method static assertSentCount($count)
 */
class Soap extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return SoapFactory::class;
    }
}
