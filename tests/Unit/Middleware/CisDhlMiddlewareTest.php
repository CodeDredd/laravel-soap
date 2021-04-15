<?php

namespace CodeDredd\Soap\Tests\Unit\Middleware;

use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\Tests\TestCase;

class CisDhlMiddlewareTest extends TestCase
{
    public function testCisDHLMiddleware()
    {
        Soap::fake();
        $client = Soap::withCisDHLAuth('test', 'dhl')->baseWsdl('https://laravel-soap.wsdl');
        $response = $client->call('Get_User');
        $lastRequest = $client->getEngine()->collectLastRequestInfo()->getLastRequest();

        self::assertTrue($response->ok());
        self::assertStringContainsString('<cis:Authentification>', $lastRequest);
    }
}
