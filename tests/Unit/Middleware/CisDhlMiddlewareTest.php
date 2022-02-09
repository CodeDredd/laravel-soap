<?php

namespace CodeDredd\Soap\Tests\Unit\Middleware;

use CodeDredd\Soap\Client\Request;
use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\Tests\TestCase;
use Illuminate\Support\Str;

class CisDhlMiddlewareTest extends TestCase
{
    public function testCisDHLMiddleware()
    {
        Soap::fake();
        $client = Soap::withCisDHLAuth('test', 'dhl')->baseWsdl(dirname(__DIR__, 2).'/Fixtures/Wsdl/weather.wsdl');
        $response = $client->call('GetWeatherInformation');
        Soap::assertSent(function (Request $request) {
            return Str::contains($request->xmlContent(), '<cis:Authentification');
        });

        self::assertTrue($response->ok());
    }
}
