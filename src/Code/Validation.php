<?php

namespace CodeDredd\Soap\Code;

use CodeDredd\Soap\Types\Service;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Wsdl2PhpGenerator\ComplexType;
use Wsdl2PhpGenerator\Enum;
use Wsdl2PhpGenerator\Operation;
use Wsdl2PhpGenerator\Variable;

/**
 * Class Validation.
 */
class Validation extends Base
{
    /**
     * @var
     */
    protected $actionName;

    /**
     * @var bool
     */
    protected $dryRun;

    /**
     * Validation constructor.
     *
     * @param  Service  $engine
     * @param $configName
     * @param  bool  $dryRun
     */
    public function __construct(Service $engine, $configName, $dryRun = false)
    {
        $this->codeClass = new ClassGenerator();
        $this->dryRun = $dryRun;
        parent::__construct($engine, $configName);
    }

    /**
     * @param  Operation  $action
     * @return ClassGenerator
     */
    public function createNewValidation(Operation $action)
    {
        $this->actionName = $action->getName();
        $className = ucfirst(Str::camel($action->getName()).'Validation');
        $validationArray = [];
        if (count($action->getParams()) > 0) {
            foreach ($action->getParams() as $key => $param) {
                $type = $this->types->get($param);
                $validationForParam = [];
                if ($type instanceof ComplexType) {
                    $validationForParam = $this->generateValidationArrayByAction($type->getMembers(), $validationForParam);
                }
                Arr::forget($validationForParam, '*');
                $validationArray[] = $validationForParam;
            }
            $validationArray = Arr::dot($validationArray);
        }
        $validatorFlags = [MethodGenerator::FLAG_PUBLIC, MethodGenerator::FLAG_STATIC];
        $validatorBody = 'return Validator::make($parameters, ['."\n"
            .$this->arrayToStringCode($validationArray)
            .']);';
        $docBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $this->actionName.' Validation',
            'longDescription' => $action->getDescription(),
        ])->setWordWrap(false);
        $this->codeClass->setName($className)
            ->setNamespaceName($this->codeNamespace.'\\Validations\\'.$this->clientClassName)
            ->setDocBlock($docBlock)
            ->addUse(Validator::class)
            ->addMethod('validator', ['parameters'], $validatorFlags, $validatorBody);

        return $this->codeClass;
    }

    /**
     * @param  array  $actionNames
     */
    public function generateValidationFiles(array $actionNames = [])
    {
        foreach ($actionNames as $actionName) {
            $action = $this->actions->get($actionName);
            if (! empty($action)) {
                $this->codeClass = $this->createNewValidation($action);
                $this->dryRun ? print_r($this->getCode()) : $this->save();
            }
        }
    }

    /**
     * @param  array  $array
     * @return string
     */
    protected function arrayToStringCode(array $array)
    {
        $stringCode = '';
        foreach ($array as $key => $value) {
            if (! empty($value)) {
                $stringCode .= "    '".$key.'\' => \''.$value."',\n";
            }
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
            } elseif ($propertyType instanceof Enum) {
                $validationArray[$property->getName()] = 'in:'.$propertyType->getValidValues();
            } else {
                $validationArray[$property->getName()] = $this->mapToValidType($property->getType()).($property->getNullable() ? '|nullable' : '');
            }
        }

        return $validationArray;
    }

    /**
     * @param $type
     * @return string
     */
    public function mapToValidType($type)
    {
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

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->codeClass->generate();
    }

    /**
     * Save generated code as file.
     */
    public function save()
    {
        $this->saveFile(
            '/Validations/'.$this->clientClassName.'/'
            .ucfirst(Str::camel($this->actionName).'Validation.php')
        );
    }
}
