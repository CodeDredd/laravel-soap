<?php

namespace CodeDredd\Soap\Xml;

use CodeDredd\Soap\Tests\TestCase;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapEngineFactory;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Phpro\SoapClient\Wsdl\Provider\LocalWsdlProvider;

class SoapXmlTest extends TestCase
{
    public function testSoapXml() {
        $file = dirname(__DIR__, 2) . '/Fixtures/Wsdl/weather.wsdl';
        $provider = LocalWsdlProvider::create();
        $provider->provide($file);
        $engine = ExtSoapEngineFactory::fromOptions(
            ExtSoapOptions::defaults($file, [])
                ->withWsdlProvider($provider)
                ->disableWsdlCache()
        );
        self::assertNotEmpty($engine->getMetadata()->getMethods());
    }
}
