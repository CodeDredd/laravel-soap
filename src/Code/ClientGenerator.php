<?php

namespace CodeDredd\Soap\Code;

use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\Types\Service;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
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
use Wsdl2PhpGenerator\Enum;
use Wsdl2PhpGenerator\Operation;
use Wsdl2PhpGenerator\Variable;

class ClientGenerator
{
    protected $types;

    protected $actions;

    protected $configName;

    protected $destination;

    protected $clientClassName;

    protected $codeNamespace;

    public function __construct(Service $engine, $configName)
    {
        $this->actions = collect($engine->getOperations());
        $this->types = collect($engine->getTypes());
        $this->configName = $configName;
        $this->clientClassName = ucfirst(Str::camel($configName));
        $this->destination = config('soap.code_path', 'Soap');
        $this->codeNamespace = config('soap.code_namespace');
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
        $validationNameSpace = $this->codeNamespace . '\\Validations\\' . $this->clientClassName;
        $clientClass = $this->createNewClient($this->configName);
        $clientClass->setNamespaceName($this->codeNamespace . '\\Clients');
        $method = $this->actions->get($actionName);
        $validationClass = $this->createNewValidation($method);
        $validationClass->setNamespaceName($validationNameSpace);
        if (!$clientClass->hasMethod($actionName)) {
            $clientClass->addMethods([$this->createNewAction($method)])
                ->addUse($validationNameSpace . '\\' . ucfirst(Str::camel($actionName).'Validation'));
        }
        echo $clientClass->generate();
        dd();
    }

    public function createNewAction(Operation $action) {
        $validationClass = ucfirst(Str::camel($action->getName()).'Validation');
        $actionBody = 'return $this->client->call(\'' . $action->getName()
            . '\', ' . $validationClass . '::validator($body));';
        return new MethodGenerator(
            $action->getName(),
            empty($action->getParams()) ? [] : [
                [
                    'name' => 'body',
                    'defaultValue' => []
                ]
            ],
            MethodGenerator::FLAG_PUBLIC,
            $actionBody,
            DocBlockGenerator::fromArray([
                'shortDescription' => 'Call action ' . $action->getName(),
                'tags' => [
                    new ParamTag('body', 'array'),
                    new ReturnTag('\CodeDredd\Soap\Client\Response')
                ]
            ])
        );
    }

    public function createNewClient($configName)
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

    public function createNewValidation(Operation $action)
    {
        $validationClass = new ClassGenerator();
        $className = ucfirst(Str::camel($action->getName()) . 'Validation');
        $validationArray = [];
        if (count($action->getParams()) > 0) {
            $requestType = Arr::first($action->getParams());

            $type = $this->types->get($requestType);
            if ($type instanceof ComplexType) {
                $validationArray = $this->generateValidationArrayByAction($type->getMembers(), $validationArray);
            }

            $validationArray = Arr::dot($validationArray);
            Arr::forget($validationArray, '*');
        }
        $validatorFlags = [MethodGenerator::FLAG_PUBLIC, MethodGenerator::FLAG_STATIC];
        $validatorBody = 'return Validator::make($parameters, [' . "\n"
            . $this->arrayToStringCode($validationArray)
            . ']);';
        $validationClass->setName($className)
            ->addUse(Validator::class)
            ->addMethod('validator', ['parameters'],$validatorFlags, $validatorBody);

        return $validationClass;
    }

    protected function arrayToStringCode(array $array) {
        $stringCode = '';
        foreach ($array as $key => $value) {
            $stringCode .= "    '" . $key . '\' => \'' . $value . "',\n";
        }
        return $stringCode;
    }

    /**
     * @param  array  $properties#
     * @param  array<Variable>  $validationArray
     * @return array
     */
    public function generateValidationArrayByAction(array $properties, $validationArray = [])
    {
        foreach ($properties as $property) {
            /** @var Variable $property */
            $propertyType = $this->types->get(Str::before($property->getType(), '[]'));
            if ($propertyType instanceof ComplexType) {
                $validationArray['*'] = 'filled';
                $validationArray[$property->getName()] = $this->generateValidationArrayByAction($propertyType->getMembers());
            } elseif($propertyType instanceof Enum) {
                $validationArray[$property->getName()] = 'in:' . $propertyType->getValidValues();
            } else {
                $validationArray[$property->getName()] = $this->mapToValidType($property->getType()) . ($property->getNullable() ? '|nullable'  : '');
            }
        }

        return $validationArray;
    }

    public function mapToValidType($type) {
        switch ($type) {
            case 'datetime': return 'date_format:Y-m-d H:i:s';
            case 'date': return 'date';
            case 'bool':
            case 'boolean': return 'boolean';
            case 'int':
            case 'Count':
            case 'Page':
            case 'integer': return 'integer';
            default: return 'string';
        }
    }
}
