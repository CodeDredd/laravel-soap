<?php

namespace CodeDredd\Soap\Client\Events;

use CodeDredd\Soap\Client\Request;
use CodeDredd\Soap\Client\Response;

class ResponseReceived
{
    /**
     * The request instance.
     *
     * @var \CodeDredd\Soap\Client\Request
     */
    public Request $request;

    /**
     * The response instance.
     *
     * @var \CodeDredd\Soap\Client\Response
     */
    public Response $response;

    /**
     * Create a new event instance.
     *
     * @param  \CodeDredd\Soap\Client\Request  $request
     * @param  \CodeDredd\Soap\Client\Response  $response
     * @return void
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
