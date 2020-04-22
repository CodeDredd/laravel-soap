<?php

namespace CodeDredd\Soap\Tests\Unit;

use CodeDredd\Soap\Client\Request;
use CodeDredd\Soap\Client\Response;
use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\Tests\TestCase;

class SoapClientTest extends TestCase
{

    /**
     * Test that a "fake" terminal returns an instance of BuilderFake
     *
     * @return void
     */
    public function testSimpleCall()
    {
        Soap::fake();
        Soap::assertNothingSent();
        $response = Soap::baseWsdl('https://laravel-soap.wsdl')
            ->call('Get_Users');
        self::assertTrue($response->ok());
        Soap::assertSent(function (Request $request) {
            return $request->action() === 'Get_Users';
        });
        Soap::assertNotSent(function (Request $request) {
            return $request->action() === 'Get_User';
        });
        Soap::assertActionCalled('Get_Users');
    }

    public function testMagicCallByConfig()
    {
        Soap::fake();
        $response = Soap::buildClient('laravel_soap')->Get_User();
        self::assertTrue($response->ok());
    }

    public function testArrayAccessResponse()
    {
	    Soap::fakeSequence()->push('test');
	    $response = Soap::buildClient('laravel_soap')->Get_User()['response'];
	    self::assertEquals('test', $response);
    }

    public function testRequestWithArguments()
    {
        Soap::fake();

        $arguments = [
            'prename' => 'Corona',
            'lastname' => 'Pandemic',
        ];

        /** @var Response $response */
        $response = Soap::buildClient('laravel_soap')->Submit_User($arguments);

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
    }

    /**
     * @dataProvider soapActionProvider
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
        $response = Soap::baseWsdl('https://laravel-soap.wsdl')
            ->call($action);
        self::assertEquals($exspected, $response->json());
    }

    public function soapActionProvider()
    {
        $fakeResponse = [
            'Get_Users' => [
                'Response_Data' => [
                    'Users' => [
                        [
                            'name' => 'test',
                            'field' => 'bla'
                        ]
                    ]
                ]
            ],
            'Get_Post' => 'Test'
        ];
        return [
            'without_fake_array' => ['Get_User', null, null],
            'with_fake_array_wrong_method' => ['Get_User', $fakeResponse, null],
            'with_fake_array' => ['Get_Users', $fakeResponse, $fakeResponse['Get_Users']],
            'with_fake_string' => ['Get_Post', $fakeResponse, ['response' => 'Test']],
        ];
    }
}
