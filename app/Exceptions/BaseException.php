<?php

declare(strict_types=1);

namespace Sputnik\Exceptions;

use Exception;

class BaseException extends Exception
{
    protected $context = [];

    public function getContext(): array
    {
        return $this->context;
    }

    protected function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }
}