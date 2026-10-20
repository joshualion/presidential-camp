<?php

namespace App\Services\Updates;

class UpdateFlowException extends UpdateException
{
    public function __construct(public string $reason, string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
