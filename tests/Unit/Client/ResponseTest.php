<?php

namespace CodeDredd\Soap\Tests\Unit\Client;

use CodeDredd\Soap\Client\Request;
use CodeDredd\Soap\Client\Response;
use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\Tests\TestCase;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Support\Str;
use VeeWee\Xml\Dom\Document;

class ResponseTest extends TestCase
{

    public function testBodyFromSoapError()
    {
        $xml = file_get_contents(dirname(__DIR__, 2) . '/Fixtures/Responses/SoapFault.xml');
        $soapResponse = new Response(new Psr7Response(400, [], $xml));
        self::assertEquals('Message was not SOAP 1.1 compliant', $soapResponse->body());
    }
}
