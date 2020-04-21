<?php

namespace CodeDredd\Soap\Client;

use Phpro\SoapClient\Type\MultiArgumentRequest;

class Request extends MultiArgumentRequest
{
    /**
     * The underlying PSR request.
     *
     * @var \Psr\Http\Message\RequestInterface
     */
    protected $request;

    /**
     * Create a new request instance.
     *
     * @param  \Psr\Http\Message\RequestInterface  $request
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
        //@todo still need to get the arguments some how
        parent::__construct([]);
    }

    /**
     * @return string
     */
    public function action(): string
    {
        return $this->request->getHeaderLine('SOAPAction');
    }
}
