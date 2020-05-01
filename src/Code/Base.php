<?php

namespace CodeDredd\Soap\Code;

use CodeDredd\Soap\Types\Service;
use Illuminate\Support\Str;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;

/**
 * Class Base.
 */
class Base
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $types;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $actions;

    /**
     * @var
     */
    protected $configName;

    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    protected $destination;

    /**
     * @var string
     */
    protected $clientClassName;

    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    protected $codeNamespace;

    /**
     * @var ClassGenerator
     */
    protected $codeClass;

    /**
     * Base constructor.
     *
     * @param  Service  $engine
     * @param $configName
     */
    public function __construct(Service $engine, $configName)
    {
        $this->actions = collect($engine->getOperations());
        $this->types = collect($engine->getTypes());
        $this->configName = $configName;
        $this->clientClassName = ucfirst(Str::camel($configName));
        $this->destination = config('soap.code.path', 'Soap');
        $this->codeNamespace = config('soap.code.namespace');
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->codeClass->generate();
    }

    /**
     * @param $filePath
     */
    public function saveFile($filePath)
    {
        $file = config('soap.code.path', app_path('Soap')).$filePath;
        if (! file_exists($file)) {
            $fileCode = FileGenerator::fromArray([
                'classes' => [$this->codeClass],
            ]);
            mkdir(dirname($file), 0777, true);
            file_put_contents($file, $fileCode->generate());
        }
    }
}
