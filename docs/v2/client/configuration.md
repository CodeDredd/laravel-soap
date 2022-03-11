# Configuration

## Soap Client Options

You may specify additional [Soap request options](https://doc.bccnsoft.com/docs/php-docs-7-en/soapclient.soapclient.html) using the `withOptions` method. The `withOptions` method accepts an array of key / value pairs:

    $response = Soap::baseWsdl(...)->withOptions([
        'trace' => true,
    ])->call(...);

By default this options are set by the Phpro package:

    'trace' => true,
    'exceptions' => true,
    'keep_alive' => true,
    'cache_wsdl' => WSDL_CACHE_DISK, // Avoid memory cache: this causes SegFaults from time to time.
    'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
    'typemap' => new TypeConverterCollection([
        new TypeConverter\DateTimeTypeConverter(),
        new TypeConverter\DateTypeConverter(),
        new TypeConverter\DecimalTypeConverter(),
        new TypeConverter\DoubleTypeConverter()
    ]),
    
## Headers

Headers may be added to requests using the `withHeaders` method. This `withHeaders` method accepts an array of key / value pairs:

    $response = Soap::withHeaders([
        'X-First' => 'foo',
        'X-Second' => 'bar'
    ])->baseWsdl('http://test.com'/v1?wsdl)->call('Get_Users');

## Custom Client Class

You are free to extend the SOAP client class used internally by this 
package, by defining your own class and extending the package client:

    use CodeDredd\Soap\SoapClient as BaseClient;
    
    class SoapClient extends BaseClient
    {
        // ...
    }

After defining your class, you may instruct the factory to use your 
custom class. Typically, this will happen in the `boot` method of 
your application's `App\Providers\AppServiceProvider` class:

    use App\Soap\SoapClient;
    use CodeDredd\Soap\SoapFactory;
    
    public function boot()
    {
        SoapFactory::useClientClass(SoapClient::class);
    }
