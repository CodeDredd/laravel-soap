<?php
namespace CodeDredd\Soap\Code;

use CodeDredd\Soap\Types\Service;
use Illuminate\Support\Str;
use Laminas\Code\Generator\DocBlock\Tag\GenericTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\InterfaceGenerator;
use Wsdl2PhpGenerator\Operation;

class ClientContract extends Base
{
    public function __construct(Service $engine, $configName)
    {
        $this->codeClass = new InterfaceGenerator();
        parent::__construct($engine, $configName);
    }

    public function createNewClientContract() {
        $className = ucfirst(Str::camel($this->configName).'Contract');
        $methodTags = $this->actions->map(function (Operation $action) {
            $params = $action->getParams() > 0 ? '($body = [])' : '()';
            return new GenericTag(
                'method',
                'CodeDredd\\Soap\\Client\\Response ' . $action->getName() . $params . $action->getDescription()
            );
        })->values()->toArray();
        $docBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $this->configName.' Client',
            'tags' => $methodTags
        ]);
        return $this->codeClass->setName($className)
            ->setDocBlock($docBlock);
    }

    public function save() {
        $this->saveFile('/Contracts/' . $this->clientClassName .'Contract.php');
    }

}
