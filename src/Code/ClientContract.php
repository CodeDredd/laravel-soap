<?php

namespace CodeDredd\Soap\Code;

use CodeDredd\Soap\Types\Service;
use Illuminate\Support\Str;
use Laminas\Code\Generator\DocBlock\Tag\GenericTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\InterfaceGenerator;
use Wsdl2PhpGenerator\Operation;

/**
 * Class ClientContract.
 */
class ClientContract extends Base
{
    /**
     * ClientContract constructor.
     *
     * @param  Service  $engine
     * @param $configName
     */
    public function __construct(Service $engine, $configName)
    {
        $this->codeClass = new InterfaceGenerator();
        parent::__construct($engine, $configName);
    }

    /**
     * @return InterfaceGenerator
     */
    public function createNewClientContract()
    {
        $className = ucfirst(Str::camel($this->configName).'Contract');
        $methodTags = $this->actions->map(function (Operation $action) {
            $params = $action->getParams() > 0 ? '($body = []) ' : '() ';

            return new GenericTag(
                'method',
                'CodeDredd\\Soap\\Client\\Response '.$action->getName().$params.$action->getDescription()
            );
        })->values()->toArray();
        $docBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $this->clientClassName.' Contract',
            'tags' => $methodTags,
        ]);

        return $this->codeClass->setName($className)
            ->setDocBlock($docBlock);
    }

    /**
     * Save generated code as file.
     */
    public function save()
    {
        $this->saveFile('/Contracts/'.$this->clientClassName.'Contract.php');
    }
}
