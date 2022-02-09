<?php

namespace CodeDredd\Soap\Commands;

use CodeDredd\Soap\Code\Client;
use CodeDredd\Soap\Types\Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeClientCommand extends Command
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
        [$wsdl, $configName] = $this->getWsdlAndConfigName();
//        $service = $this->getGeneratorService($wsdl);
//
//        $client = new Client($service, $configName);
//        if ($this->option('dry-run')) {
//            echo $client->getCode();
//        } else {
//            $client->save();
//        }
    }

    public function getGeneratorService($wsdl, Command $commandInstance = null)
    {
        $commandInstance = $commandInstance ?? $this;
        $commandInstance->line('Loading wsdl configuration ...');
        // Refactor code generation
//        $generator = new Generator();
//        $generator->setConfigByWsdl($wsdl, $this->output);
//
//        return $generator->getService();
    }

    public function getWsdlAndConfigName(Command $commandInstance = null)
    {
        $commandInstance = $commandInstance ?? $this;
        $clients = config('soap.clients');
        $clientNames = [];

        if (! empty($clients)) {
            $clientNames = array_keys($clients);
        }
        $wsdl = $commandInstance->anticipate('Please type the wsdl or the name of your client configuration if u have defined one in the config "soap.php"', $clientNames);
        if (! Str::contains($wsdl, ['http:', 'https:'])) {
            $configName = $wsdl;
            $wsdl = config()->get('soap.clients.'.$wsdl.'.base_wsdl');
        } else {
            $configName = $commandInstance->ask('Please give a name under which the code will be generated. E.g. "laravel_soap"');
        }

        return [$wsdl, $configName];
    }
}
