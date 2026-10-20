<?php

namespace App\Services\Updates;

class UpdateFlowException extends UpdateException
{
    public function __construct(public string $reason, string $message)
    {
        parent::__construct($message);
    }
}
