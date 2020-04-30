<?php
namespace CodeDredd\Soap\Code;

use CodeDredd\Soap\Types\Service;
use Illuminate\Support\Str;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;

class Base
{

    protected $types;

    protected $actions;

    protected $configName;

    protected $destination;

    protected $clientClassName;

    protected $codeNamespace;

    /** @var ClassGenerator $codeClass */
    protected $codeClass;

    public function __construct(Service $engine, $configName)
    {
        $this->actions = collect($engine->getOperations());
        $this->types = collect($engine->getTypes());
        $this->configName = $configName;
        $this->clientClassName = ucfirst(Str::camel($configName));
        $this->destination = config('soap.code.path', 'Soap');
        $this->codeNamespace = config('soap.code.namespace');
    }

    public function getCode() {
        return $this->codeClass->generate();
    }

    public function saveFile($filePath) {
        $file = config('soap.code.path', app_path('Soap')) . $filePath;
        if (!file_exists($file)) {
            $fileCode = FileGenerator::fromArray([
                'classes' => [$this->codeClass]
            ]);
            mkdir(dirname($file), 0777, true);
            file_put_contents($file, $fileCode->generate());
        }
    }
}
