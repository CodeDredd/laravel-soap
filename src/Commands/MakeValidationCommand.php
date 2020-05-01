<?php

namespace CodeDredd\Soap\Commands;

use CodeDredd\Soap\Code\Validation;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MakeValidationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soap:make:validation {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a validation by wsdl/config for your soap client';

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
        $clientCommand = new MakeClientCommand();
        [$wsdl, $configName] = $clientCommand->getWsdlAndConfigName($this);
        $service = $clientCommand->getGeneratorService($wsdl, $this);

        $generateAllClassMaps = $this->confirm('Do you want to generate for every client method a validation?');
        $methods = collect($service->getOperations());
        $validationClasses = $methods->keys()->toArray();
        if (! $generateAllClassMaps) {
            $validationClasses = Arr::wrap($this->anticipate('Which method do you want to generate?', $methods->keys()->toArray()));
        }
        $validationCode = new Validation($service, $configName, $this->option('dry-run'));
        $validationCode->generateValidationFiles($validationClasses);
    }
}
