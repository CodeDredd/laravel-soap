<?php
/**
 * Created by PhpStorm.
 * User: Gregor Becker <gregor.becker@getinbyte.com>
 * Date: 27.04.2020
 * Time: 17:21.
 */

namespace CodeDredd\Soap\Types;

use Wsdl2PhpGenerator\ConfigInterface;
use Wsdl2PhpGenerator\Operation;

class Service extends \Wsdl2PhpGenerator\Service
{
    protected $operations;

    public function __construct(ConfigInterface $config, $identifier, array $types, $description)
    {
        $this->operations = [];
        parent::__construct($config, $identifier, $types, $description);
    }

    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * Returns an operation provided by the service based on its name.
     *
     * @param string $operationName the name of the operation
     *
     * @return Operation|null the operation or null if it does not exist
     */
    public function getOperation($operationName)
    {
        return isset($this->operations[$operationName]) ? $this->operations[$operationName] : null;
    }

    /**
     * Add an operation to the service.
     *
     * @param Operation $operation the operation to be added
     */
    public function addOperation(Operation $operation)
    {
        $this->operations[$operation->getName()] = $operation;
    }
}
