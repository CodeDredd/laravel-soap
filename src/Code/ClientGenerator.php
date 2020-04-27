<?php

namespace CodeDredd\Soap\Code;

use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\Types\Service;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Phpro\SoapClient\Exception\MetadataException;
use Phpro\SoapClient\Soap\Engine\Engine;
use Phpro\SoapClient\Soap\Engine\Metadata\Model\Method;
use Phpro\SoapClient\Soap\Engine\Metadata\Model\Property;
use Phpro\SoapClient\Soap\Engine\Metadata\Model\Type;
use Wsdl2PhpGenerator\ComplexType;
use Wsdl2PhpGenerator\Operation;
use Wsdl2PhpGenerator\Variable;

class ClientGenerator
{
    protected $types;

    protected $actions;

    protected $configName;

    protected $destination;

    protected $clientClassName;

    public function __construct(Service $engine, $configName)
    {
        $this->actions = $engine->getOperations();
        $this->types = $engine->getTypes();
        $this->configName = $configName;
        $this->clientClassName = ucfirst(Str::camel($configName));
        $this->destination = config('soap.code_path', 'Soap');
    }

    public static function clientMethod(Method $method)
    {
        $newMethodClass = new ClassGenerator();
        $docBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $method->getName()
        ]);
        $newMethodClass->setName($method->getName())
            ->setDocBlock($docBlock);
        echo $newMethodClass->generate();
        dd();
    }

    public function generate($actionName = '')
    {
        $clientClass = $this->createNewClient($this->configName, $this->destination);
        $method = $this->actions[$actionName];
        $validationClass = $this->createNewValidation($method, $this->configName, $this->destination);
        dd();
    }

    public function createNewClient($configName, $destination)
    {
        $clientClass = new ClassGenerator();
        $className = ucfirst(Str::camel($configName).'Client');
        $docBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $configName.' Client'
        ]);
        $constructorDocBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $className.' constructor'
        ]);
        $callDocBlock = DocBlockGenerator::fromArray([
            'shortDescription' => 'Execute soap call',
            'tags' => [
                new ParamTag('method', 'string'),
                new ParamTag('parameters', 'mixed'),
                new ReturnTag('\CodeDredd\Soap\Client\Response|mixed')
            ]
        ]);
        $callMethodParameters = [
            'method',
            'parameters'
        ];
        $callMethodBody = 'if (static::hasMacro($method)) {'."\n\x20\x20\x20\x20"
            .'return $this->macroCall($method, $parameters);'."\n"
            .'}'."\n\n"
            .'return $this->client->call($method, $parameters);';
        $clientClass->setName($className)
            ->setDocBlock($docBlock)
            ->addProperty('client', null, PropertyGenerator::FLAG_PROTECTED)
            ->addUse(Soap::class)
            ->addUse(Macroable::class)
            ->setNamespaceName('App\\'.$destination.'\\Clients')
            ->addTrait('Macroable')
            ->addTraitAlias('Macroable::__call', 'macroCall')
            ->addMethods([
                new MethodGenerator('__construct', [], MethodGenerator::FLAG_PUBLIC,
                    '$this->client = Soap::buildClient(\''.$configName.'\')', $constructorDocBlock),
                new MethodGenerator('__call', $callMethodParameters, MethodGenerator::FLAG_PUBLIC, $callMethodBody,
                    $callDocBlock),
            ]);

        return $clientClass;
    }

    public function createNewValidation(Operation $action, $configName, $destination)
    {
        $validationClass = new ClassGenerator();
        $className = ucfirst(Str::camel($action->getName()));
        $validationArray = [];
        if (count($action->getParams()) > 0) {
            $requestType = Arr::first($action->getParams());

            $type = $this->types[$requestType];
            if ($type instanceof ComplexType) {
                $validationArray = $this->generateValidationArrayByAction($type->getMembers(), $validationArray);
            }
            dd($type);

            $validationArray = Arr::dot($validationArray);
            Arr::forget($validationArray, '*');
            dd($validationArray);
        }
        $validationClass->setName($className);

        return $validationClass;
    }

    /**
     * @param  array  $properties#
     * @param  array<Variable>  $validationArray
     * @return array
     */
    public function generateValidationArrayByAction(array $properties, $validationArray = [])
    {
//        dd();
        dd($this->types->map(function ($type) {
            return $type->getName();
        }));
//        dd($this->types->fetchOneByName('CustomerReferenceEnumeration'));
            foreach ($properties as $property) {
                try {
                    /** @var Property $property */
                    $propertyType = $this->types->fetchOneByName($property->getType()->getName());
                    $validationArray['*'] = 'filled';
                    $validationArray[$property->getName()] = $this->generateValidationArrayByAction($propertyType->getProperties());
                } catch (MetadataException $exception) {
                    $validationArray[$property->getName()] = $property->getType()->getName();
                }
            }

        return $validationArray;
    }
}
