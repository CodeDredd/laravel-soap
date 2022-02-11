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
        $xml = <<<XML
<?xml version='1.0' encoding='UTF-8'?>
<soap:Envelope xmlns:soap='http://schemas.xmlsoap.org/soap/envelope'>
   <soap:Body>
      <soap:Fault>
         <faultcode>soap:VersionMismatch</faultcode>
         <faultstring xml:lang='en'>
            Message was not SOAP 1.1 compliant
         </faultstring>
         <faultactor>
            http://sample.org.ocm/jws/authnticator
         </faultactor>
      </soap:Fault>
   </soap:Body>
</soap:Envelope>
XML;
        $soapResponse = new Response(new Psr7Response(400, [], $xml));
        self::assertEquals('Message was not SOAP 1.1 compliant', $soapResponse->body());
    }
}
