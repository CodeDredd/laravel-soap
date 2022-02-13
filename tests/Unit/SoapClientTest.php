<?php

namespace CodeDredd\Soap\Tests\Unit;

use CodeDredd\Soap\Client\Events\ConnectionFailed;
use CodeDredd\Soap\Client\Events\RequestSending;
use CodeDredd\Soap\Client\Events\ResponseReceived;
use CodeDredd\Soap\Client\Request;
use CodeDredd\Soap\Client\Response;
use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\SoapClient;
use CodeDredd\Soap\SoapFactory;
use CodeDredd\Soap\Tests\Fixtures\CustomSoapClient;
use CodeDredd\Soap\Tests\TestCase;
use GuzzleHttp\RedirectMiddleware;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;

class SoapClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test that a "fake" terminal returns an instance of BuilderFake.
     *
     * @return void
     */
    public function testSimpleCall()
    {
        Soap::fake();
        Event::fake();
        Soap::assertNothingSent();
        $response = Soap::baseWsdl(dirname(__DIR__, 1).'/Fixtures/Wsdl/weather.wsdl')
            ->call('GetWeatherInformation');
        self::assertTrue($response->ok());
        Soap::assertSent(function (Request $request) {
            return $request->action() === 'GetWeatherInformation';
        });
        Soap::assertNotSent(function (Request $request) {
            return $request->action() === 'GetCityWeatherByZIPSoapOut';
        });
        Soap::assertActionCalled('GetWeatherInformation');
    }

    public function testMagicCallByConfig()
    {
        Soap::fake();
        Event::fake();
        $response = Soap::buildClient('laravel_soap')->GetWeatherInformation();
        self::assertTrue($response->ok());
    }

    public function testWsseWithWsaCall()
    {
        Soap::fake();
        ray()->showSoapClientRequests();
        $client = Soap::baseWsdl(dirname(__DIR__, 1).'/Fixtures/Wsdl/weather.wsdl')->withWsse([
            'userTokenName' => 'Test',
            'userTokenPassword' => 'passwordTest',
            'mustUnderstand' => false,
        ])->withWsa();
        $response = $client->GetWeatherInformation();
        Soap::assertSent(function (Request $request) {
            return ! Str::contains($request->xmlContent(), 'mustUnderstand');
        });
        self::assertTrue($response->ok());
    }

    public function testArrayAccessResponse()
    {
        Soap::fakeSequence()->push('test');
        Event::fake();
        $response = Soap::buildClient('laravel_soap')->GetWeatherInformation()['response'];
        self::assertEquals('test', $response);
    }

    public function testRequestWithArguments()
    {
        Soap::fake();
        Event::fake();

        $arguments = [
            'prename' => 'Corona',
            'lastname' => 'Pandemic',
        ];

        /** @var Response $response */
        $response = Soap::buildClient('laravel_soap')->Submit_User($arguments);

        Event::assertDispatched(RequestSending::class);
        Event::assertDispatched(ResponseReceived::class);
        self::assertTrue($response->ok());
        Soap::assertSent(function (Request $request) use ($arguments) {
            return $request->arguments() === $arguments &&
                $request->action() === 'Submit_User';
        });
    }

    public function testSequenceFake()
    {
        $responseFake = ['user' => 'test'];
        $responseFake2 = ['user' => 'test2'];
        Event::fake();
        Soap::fakeSequence()
            ->push($responseFake)
            ->whenEmpty(Soap::response($responseFake2));
        $client = Soap::buildClient('laravel_soap');
        $response = $client->Get_User();
        $response2 = $client->Get_User();
        $response3 = $client->Get_User();
        self::assertTrue($response->ok());
        self::assertEquals($responseFake, $response->json());
        self::assertEquals($responseFake2, $response2->json());
        self::assertEquals($responseFake2, $response3->json());

        Soap::assertSentCount(3);
    }

    /**
     * @dataProvider soapActionProvider
     *
     * @param $action
     * @param $fake
     * @param $exspected
     */
    public function testSoapFake($action, $fake, $exspected)
    {
        $fake = collect($fake)->map(function ($item) {
            return Soap::response($item);
        })->all();

        Soap::fake($fake);
        Event::fake();
        Event::assertNotDispatched(RequestSending::class);
        Event::assertNotDispatched(ResponseReceived::class);
        $response = Soap::baseWsdl(dirname(__DIR__, 1).'/Fixtures/Wsdl/weather.wsdl')
            ->call($action);
        Event::assertDispatched(RequestSending::class);
        Event::assertDispatched(ResponseReceived::class);
        Event::assertNotDispatched(ConnectionFailed::class);
        self::assertEquals($exspected, $response->json());
    }

    #[ArrayShape([
        'without_fake_array' => 'array',
        'with_fake_array_wrong_method' => 'array',
        'with_fake_array' => 'array',
        'with_fake_string' => 'array',
    ])]
    public function soapActionProvider(): array
    {
        $fakeResponse = [
            'GetWeatherInformation' => [
                'Response_Data' => [
                    'Users' => [
                        [
                            'name' => 'test',
                            'field' => 'bla',
                        ],
                    ],
                ],
            ],
            'GetCityForecastByZIP' => 'Test',
        ];

        return [
            'without_fake_array' => ['GetCityWeatherByZIP', null, null],
            'with_fake_array_wrong_method' => ['GetCityWeatherByZIP', $fakeResponse, null],
            'with_fake_array' => ['GetWeatherInformation', $fakeResponse, $fakeResponse['GetWeatherInformation']],
            'with_fake_string' => ['GetCityForecastByZIP', $fakeResponse, ['response' => 'Test']],
        ];
    }

    public function testSoapOptions(): void
    {
        Soap::fake();
        Event::fake();
        $client = Soap::withOptions(['soap_version' => SOAP_1_2])
            ->baseWsdl(dirname(__DIR__, 1).'/Fixtures/Wsdl/weather.wsdl');
        $response = $client->call('GetWeatherInformation');
        self::assertTrue($response->ok());
        Soap::assertSent(function (Request $request) {
            return Str::contains(
                $request->getRequest()->getHeaderLine('Content-Type'),
                'application/soap+xml; charset="utf-8"'
            );
        });
        Soap::assertActionCalled('GetWeatherInformation');
    }

    public function testRealSoapCall(): void
    {
        $this->markTestSkipped('Real Soap Call Testing. Comment the line out for testing');
        ray()->showSoapClientRequests();
        // location has to be set because the wsdl has a wrong location declaration
        $client = Soap::baseWsdl('https://www.w3schools.com/xml/tempconvert.asmx?wsdl')
            ->withOptions([
                'soap_version' => SOAP_1_2,
                'location' => 'https://www.w3schools.com/xml/tempconvert.asmx?wsdl',
            ]);
        $result = $client->call('FahrenheitToCelsius', [
            'Fahrenheit' => 75,
        ]);
        self::assertArrayHasKey('FahrenheitToCelsiusResult', $result->json());

        $result = $client->FahrenheitToCelsius([
            'Fahrenheit' => 75,
        ]);
        self::assertArrayHasKey('FahrenheitToCelsiusResult', $result->json());
    }

    public function testRealSoapCallBank(): void
    {
        $this->markTestSkipped('Real Soap Call Testing. Comment the line out for testing');
        ray()->showSoapClientRequests();
        // location has to be set because the wsdl has a wrong location declaration
        $client = Soap::baseWsdl('http://www.thomas-bayer.com/axis2/services/BLZService?wsdl')
            ->withOptions([
                'soap_version' => SOAP_1_2,
                'location' => 'http://www.thomas-bayer.com/axis2/services/BLZService?wsdl',
            ]);
        $result = $client->call('getBank', [
            'blz' => '74120071',
        ]);
        dd($result->json());
        self::assertArrayHasKey('FahrenheitToCelsiusResult', $result->json());

        $result = $client->FahrenheitToCelsius([
            'Fahrenheit' => 75,
        ]);
        self::assertArrayHasKey('FahrenheitToCelsiusResult', $result->json());
    }

    /**
     * @dataProvider soapHeaderProvider
     *
     * @param $header
     * @param $exspected
     */
    public function testSoapWithDifferentHeaders($header, $exspected): void
    {
        Soap::fake();
        Event::fake();
        $client = Soap::withHeaders($header)->baseWsdl(dirname(__DIR__, 1).'/Fixtures/Wsdl/weather.wsdl');
        $response = $client->call('GetWeatherInformation');
        Soap::assertSent(function (Request $request) use ($exspected) {
            return $request->getRequest()->getHeaderLine('test') === $exspected;
        });
        self::assertTrue($response->ok());
        Soap::assertActionCalled('GetWeatherInformation');
    }

    public function soapHeaderProvider(): array
    {
        $header = [
            'test' => 'application/soap+xml; charset="utf-8"',
        ];

        return [
            'without_header' => [[], ''],
            'with_header' => [$header, $header['test']],
        ];
    }

    public function testSoapClientClassMayBeCustomized(): void
    {
        Soap::fake();
        Event::fake();
        $client = Soap::buildClient('laravel_soap');
        $this->assertInstanceOf(SoapClient::class, $client);
        SoapFactory::useClientClass(CustomSoapClient::class);
        $client = Soap::buildClient('laravel_soap');
        $this->assertInstanceOf(CustomSoapClient::class, $client);
    }

    public function testHandlerOptions(): void
    {
        Soap::fake();
        Event::fake();
        $client = Soap::baseWsdl(dirname(__DIR__, 1).'/Fixtures/Wsdl/weather.wsdl');
        $response = $client->call('GetWeatherInformation');
        self::assertTrue($response->ok());
        self::assertEquals(true, $client->getClient()->getConfig()['verify']);
        $client = $client->withGuzzleClientOptions([
            'allow_redirects' => RedirectMiddleware::$defaultSettings,
            'http_errors' => true,
            'decode_content' => true,
            'verify' => false,
            'cookies' => false,
            'idn_conversion' => false,
        ]);
        $response = $client->call('GetWeatherInformation');
        self::assertTrue($response->ok());
        self::assertEquals(false, $client->getClient()->getConfig()['verify']);
    }
}
