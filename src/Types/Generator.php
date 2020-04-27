<?php
namespace CodeDredd\Soap\Types;

use Wsdl2PhpGenerator\Config;
use Wsdl2PhpGenerator\Operation;

class Generator extends \Wsdl2PhpGenerator\Generator
{
    /**
     * @var Service
     */
    protected $service;

    public function setConfigByWsdl($wsdl) {
        $this->config = new Config([
            'inputFile' => $wsdl,
            'outputDir' => '/tmp/output'
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
    public function getTypes() {
        return $this->types;
    }

    public function getService() {
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
}
