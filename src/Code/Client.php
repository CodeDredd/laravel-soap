<?php

namespace CodeDredd\Soap\Code;

use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\Types\Service;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag\GenericTag;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Wsdl2PhpGenerator\Operation;

/**
 * Class Client.
 */
class Client extends Base
{
    /**
     * @var ClientContract
     */
    protected $clientContract;

    /**
     * Client constructor.
     *
     * @param  Service  $engine
     * @param $configName
     */
    public function __construct(Service $engine, $configName)
    {
        parent::__construct($engine, $configName);
        $this->codeClass = new ClassGenerator();
        $this->clientContract = new ClientContract($engine, $configName);
        $this->createNewClient();
    }

    /**
     * @return ClassGenerator
     */
    public function createNewClient()
    {
        $this->clientContract->createNewClientContract();
        $className = ucfirst(Str::camel($this->configName).'Client');
        $methodTags = $this->actions->map(function (Operation $action) {
            $params = $action->getParams() > 0 ? '($body = [])' : '()';

            return new GenericTag(
                'method',
                'CodeDredd\\Soap\\Client\\Response '.$action->getName().$params
            );
        })->values()->toArray();
        $docBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $this->clientClassName.' Client',
            'tags' => $methodTags,
        ])->setWordWrap(false);
        $constructorDocBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $className.' constructor',
        ]);
        $callDocBlock = DocBlockGenerator::fromArray([
            'shortDescription' => 'Execute soap call',
            'tags' => [
                new ParamTag('method', 'string'),
                new ParamTag('parameters', 'mixed'),
                new ReturnTag('\CodeDredd\Soap\Client\Response|mixed'),
            ],
        ])->setWordWrap(false);
        $callMethodParameters = [
            'method',
            'parameters',
        ];
        $callMethodBody = 'if (static::hasMacro($method)) {'."\n    "
            .'return $this->macroCall($method, $parameters);'."\n"
            .'}'."\n\n"
            .'$validationClass = \''.addslashes($this->codeNamespace.'\Validations\LaravelSoap\\')."'\n    "
            .'. ucfirst(Str::camel($method))'."\n    "
            .'. \'Validation\';'."\n"
            .'if (class_exists($validationClass)) {'."\n    "
            .'$parameters = app()->call([$validationClass, \'validator\'], [\'parameters\' => $parameters]);'."\n"
            .'}'."\n\n"
            .'return $this->client->call($method, $parameters);';

        $this->codeClass->setName($className)
            ->setDocBlock($docBlock)
            ->setNamespaceName($this->codeNamespace.'\\Clients')
            ->setImplementedInterfaces([$this->codeNamespace.'\\Contracts\\'.$this->clientClassName.'Contract'])
            ->addProperty('client', null, PropertyGenerator::FLAG_PROTECTED)
            ->addUse(Soap::class)
            ->addUse(Macroable::class)
            ->addTrait('Macroable')
            ->addTraitAlias('Macroable::__call', 'macroCall')
            ->addMethods([
                new MethodGenerator('__construct', [], MethodGenerator::FLAG_PUBLIC,
                    '$this->client = Soap::buildClient(\''.$this->configName.'\');', $constructorDocBlock),
                new MethodGenerator('__call', $callMethodParameters, MethodGenerator::FLAG_PUBLIC, $callMethodBody,
                    $callDocBlock),
            ]);

        return $this->codeClass;
    }

    /**
     * @param  Operation  $action
     * @return MethodGenerator
     */
    public static function createNewAction(Operation $action)
    {
        $validationClass = ucfirst(Str::camel($action->getName()).'Validation');
        $actionBody = 'return $this->client->call(\''.$action->getName()
            .'\', '.$validationClass.'::validator($body));';

        return new MethodGenerator(
            $action->getName(),
            empty($action->getParams()) ? [] : [
                [
                    'name' => 'body',
                    'defaultValue' => [],
                ],
            ],
            MethodGenerator::FLAG_PUBLIC,
            $actionBody,
            DocBlockGenerator::fromArray([
                'shortDescription' => 'Call action '.$action->getName(),
                'tags' => [
                    new ParamTag('body', 'array'),
                    new ReturnTag('\CodeDredd\Soap\Client\Response'),
                ],
            ])
        );
    }

    /**
     * Save generated code as file.
     */
    public function save()
    {
        $this->clientContract->save();
        $this->saveFile('/Clients/'.$this->clientClassName.'Client.php');
    }
}
