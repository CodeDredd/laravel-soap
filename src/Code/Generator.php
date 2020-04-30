<?php

namespace CodeDredd\Soap\Code;

use CodeDredd\Soap\Types\Service;
use Illuminate\Support\Str;

class Generator extends Base
{
    protected $clientCode;
    protected $validationCode;

    public function __construct(Service $engine, $configName)
    {
        $this->clientCode = new Client($engine, $configName);
        $this->validationCode = new Validation($engine, $configName);
        parent::__construct($engine, $configName);
    }

//    public function generateClient(Service $engine, $configName, $dryRun)
//    {
//
//        $validationNameSpace = $this->codeNamespace . '\\Validations\\' . $this->clientClassName;
//        $clientInterface = $this->createNewClientContract($this->configName);
//        $clientInterface->setNamespaceName($this->codeNamespace . '\\Contracts');
//        $clientClass = Client::createNewClient($this->configName, $this->actions);
//        $clientClass->setNamespaceName($this->codeNamespace . '\\Clients')
//            ->addUse($this->codeNamespace . '\\Contracts\\' . $this->clientClassName . 'Contract')
//            ->setImplementedInterfaces([$this->clientClassName . 'Contract']);
//        $method = $this->actions->get($actionName);
//        $validationClass = $this->createNewValidation($method);
//        $validationClass->setNamespaceName($validationNameSpace);
//        if (!$clientClass->hasMethod($actionName)) {
//            $clientClass->addMethods([Client::createNewAction($method)])
//                ->addUse($validationNameSpace . '\\' . ucfirst(Str::camel($actionName).'Validation'));
//        }
//        echo $clientClass->generate();
//        dd();
//    }

    public function generateValidationFiles(array $actionNames = []) {
        if (empty($actionNames)) {
            $this->actions->each(function ($action) {
                $validationClass = $this->validationCode->createNewValidation($action);
            });
        } else {
            foreach ($actionNames as $actionName) {
                $method = $this->actions->get($actionName);
                if (!empty($method)) {
                    $methods[] = $method;
                }
            }
        }

    }

    public function generateValidationCode($action) {
        $validationClass = $this->validationCode->createNewValidation($action);
    }


}
