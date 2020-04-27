<?php

namespace CodeDredd\Soap\Code;

use Laminas\Code\Generator\ClassGenerator as LaminasClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Phpro\SoapClient\Soap\Engine\Engine;
use Phpro\SoapClient\Soap\Engine\Metadata\Model\Method;

class ClientGenerator
{
    protected $engine;

    public function __construct(Engine $engine, $configName)
    {
        $this->engine = $engine;
    }

    public static function clientMethod(Method $method) {
        $newMethodClass = new LaminasClassGenerator();
        $docBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $method->getName()
        ]);
        $newMethodClass->setName($method->getName())
            ->setDocBlock($docBlock);
        echo $newMethodClass->generate();
        dd();
        $destination = app_path(config('soap.code_path'));
    }

    public static function createClient(Method $method) {
        $newMethodClass = new LaminasClassGenerator();
        $docBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $method->getName()
        ]);
        $newMethodClass->setName($method->getName())
            ->setDocBlock($docBlock);
    }
}
