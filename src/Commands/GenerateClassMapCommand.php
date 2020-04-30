<?php

namespace CodeDredd\Soap\Commands;


use CodeDredd\Soap\Code\Client;
use CodeDredd\Soap\Code\ClientContract;
use CodeDredd\Soap\Types\Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapEngineFactory;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Phpro\SoapClient\Soap\Engine\Metadata\Model\Method;

class GenerateClassMapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soap:make:client {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a custom client with all possible methods by wsdl';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $clients = config('soap.clients');
        $clientNames = [];

        if (!empty($clients)) {
            $clientNames = array_keys($clients);
        }
        $wsdl = $this->anticipate('Please type the wsdl or the name of your client configuration if u have defined one in the config "soap.php"', $clientNames);
        if(!Str::contains($wsdl, ['http:', 'https:'])) {
            $configName = $wsdl;
            $wsdl = config()->get('soap.clients.' . $wsdl . '.base_wsdl');
        } else {
            $configName = $this->ask('Please give a name under which the code will be generated. E.g. "laravel_soap"');
        }
//        $generateAllClassMaps = $this->confirm('Do you want to generate all client methods?');
//        $singleClass = '';
        $generator = new Generator();
        $generator->setConfigByWsdl($wsdl);
        $clientContract = new ClientContract($generator->getService(), $configName);
        $client = new Client($generator->getService(), $configName);
        $client->createNewClient();
        if ($this->option('dry-run')) {
            echo $client->getCode();
        } else {
            $client->save();
        }


//        $methods = collect($generator->getService()->getOperations());
//        if (!$generateAllClassMaps) {
//            $singleClass = $this->anticipate('Which method do you want to generate?', $methods->keys()->toArray());
//        }
//        $codeGenerator = new Generator($generator->getService(), $configName);
//        $codeGenerator->generate($singleClass);
//        Generator::clientMethod($methods->get($singleClass));
    }

}
