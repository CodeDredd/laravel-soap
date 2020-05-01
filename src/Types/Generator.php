<?php

namespace CodeDredd\Soap\Types;

use Illuminate\Console\OutputStyle;
use Wsdl2PhpGenerator\ArrayType;
use Wsdl2PhpGenerator\ComplexType;
use Wsdl2PhpGenerator\Config;
use Wsdl2PhpGenerator\Enum;
use Wsdl2PhpGenerator\Operation;
use Wsdl2PhpGenerator\Pattern;

class Generator extends \Wsdl2PhpGenerator\Generator
{
    /**
     * @var Service
     */
    protected $service;

    protected $output;

    public function setConfigByWsdl($wsdl, OutputStyle $output = null)
    {
        $this->config = new Config([
            'inputFile' => $wsdl,
            'outputDir' => '/tmp/output',
        ]);

        $this->log('Starting generation');

        // Warn users who have disabled SOAP_SINGLE_ELEMENT_ARRAYS.
        // Note that this can be
        $options = $this->config->get('soapClientOptions');
        if (empty($options['features']) ||
            (($options['features'] & SOAP_SINGLE_ELEMENT_ARRAYS) != SOAP_SINGLE_ELEMENT_ARRAYS)) {
            $message = ['SoapClient option feature SOAP_SINGLE_ELEMENT_ARRAYS is not set.',
                'This is not recommended as data types in DocBlocks for array properties will not be ',
                'valid if the array only contains a single value.', ];
            $this->log(implode(PHP_EOL, $message), 'warning');
        }
        $this->load($wsdl);
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function getService()
    {
        return $this->service;
    }

    protected function loadService()
    {
        $service = $this->wsdl->getService();
        $this->log('Starting to load service '.$service->getName());

        $this->service = new Service($this->config, $service->getName(), $this->types, $service->getDocumentation());

        foreach ($this->wsdl->getOperations() as $function) {
            $this->log('Loading function '.$function->getName());

            $this->service->addOperation(new Operation($function->getName(), $function->getParams(), $function->getDocumentation(), $function->getReturns()));
        }

        $this->log('Done loading service '.$service->getName());
    }

    /**
     * Loads all type classes.
     */
    protected function loadTypes()
    {
        $this->log('Loading types');

        $types = $this->wsdl->getTypes();

        foreach ($types as $typeNode) {
            $type = null;

            if ($typeNode->isComplex()) {
                if ($typeNode->isArray()) {
                    $type = new ArrayType($this->config, $typeNode->getName());
                } else {
                    $type = new ComplexType($this->config, $typeNode->getName());
                }

                $this->log('Loading type '.$type->getPhpIdentifier());

                $type->setAbstract($typeNode->isAbstract());

                foreach ($typeNode->getParts() as $name => $typeName) {
                    // There are 2 ways a wsdl can indicate that a field accepts the null value -
                    // by setting the "nillable" attribute to "true" or by setting the "minOccurs" attribute to "0".
                    // See http://www.ibm.com/developerworks/webservices/library/ws-tip-null/index.html
                    $nullable = $typeNode->isElementNillable($name) || $typeNode->getElementMinOccurs($name) === 0;
                    $type->addMember($typeName, $name, $nullable);
                }
            } elseif ($enumValues = $typeNode->getEnumerations()) {
                $type = new Enum($this->config, $typeNode->getName(), $typeNode->getRestriction());
                array_walk($enumValues, function ($value) use ($type) {
                    $type->addValue($value);
                });
            } elseif ($pattern = $typeNode->getPattern()) {
                $type = new Pattern($this->config, $typeNode->getName(), $typeNode->getRestriction());
                $type->setValue($pattern);
            }

            if ($type != null) {
                $already_registered = false;
                if ($this->config->get('sharedTypes')) {
                    foreach ($this->types as $registered_types) {
                        if ($registered_types->getIdentifier() == $type->getIdentifier()) {
                            $already_registered = true;
                            break;
                        }
                    }
                }
                if (! $already_registered) {
                    $this->types[$typeNode->getName()] = $type;
                }
            }
        }

        // Loop through all types again to setup class inheritance.
        // We can only do this once all types have been loaded. Otherwise we risk referencing types which have not been
        // loaded yet.
        foreach ($types as $type) {
            if (($baseType = $type->getBase()) && isset($this->types[$baseType]) && $this->types[$baseType] instanceof ComplexType) {
                $this->types[$type->getName()]->setBaseType($this->types[$baseType]);
            }
        }

        $this->log('Done loading types');
    }
}
