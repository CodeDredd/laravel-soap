<?php

namespace CodeDredd\Soap\Xml;

use CodeDredd\Soap\Tests\TestCase;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapEngineFactory;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Phpro\SoapClient\Wsdl\Provider\LocalWsdlProvider;

class XmlSerializerTest extends TestCase
{
    protected $xml = <<<XML
<?xml version="1.0"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
    <SOAP-ENV:Body>
        <SOAP-ENV:prename>Code</SOAP-ENV:prename>
        <SOAP-ENV:lastname>dredd</SOAP-ENV:lastname>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

    protected $array = [
        'prename' => 'Code',
        'lastname' => 'dredd'
    ];

    public function testArrayToSoapXml()
    {
        $soapXml = XMLSerializer::arrayToSoapXml($this->array);

        self::assertXmlStringEqualsXmlString($this->xml, $soapXml);
    }

    public function testDomNodeToArray()
    {
        $xmlDocument = SoapXml::fromString($this->xml);
        $xmlBodyAsArray = XMLSerializer::domNodeToArray($xmlDocument->getBody());

        self::assertEquals($this->array, $xmlBodyAsArray);
    }
}
