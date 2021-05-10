<?php

namespace CodeDredd\Soap\Events;

use Illuminate\Foundation\Events\Dispatchable;

class RequestHandled
{
    use Dispatchable;
    /**
     * The request instance.
     *
     * @var \CodeDredd\Soap\Client\Request
     */
    public $request;

    /**
     * The response instance.
     *
     * @var \CodeDredd\Soap\Client\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \CodeDredd\Soap\Client\Request  $request
     * @param  \CodeDredd\Soap\Client\Response  $response
     * @return void
     */
    public function __construct($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
