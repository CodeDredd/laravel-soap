<?php

namespace CodeDredd\Soap\Client\Events;

use CodeDredd\Soap\Client\Request;

class ConnectionFailed
{
    /**
     * The request instance.
     *
     * @var \CodeDredd\Soap\Client\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \CodeDredd\Soap\Client\Request  $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
