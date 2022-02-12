<?php

namespace CodeDredd\Soap\Client\Events;

use CodeDredd\Soap\Client\Request;

class RequestSending
{
    /**
     * The request instance.
     */
    public Request $request;

    /**
     * Create a new event instance.
     *
     * @param  Request  $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
