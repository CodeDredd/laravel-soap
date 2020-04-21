<?php

namespace CodeDredd\Soap\Exceptions;

use RuntimeException;

class NotFoundConfigurationException extends RuntimeException
{
    /**
     * @param  \Throwable  $throwable
     *
     * @return NotFoundConfigurationException
     */
    public static function fromThrowable(\Throwable $throwable): self
    {
        return new self($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
    }
}
