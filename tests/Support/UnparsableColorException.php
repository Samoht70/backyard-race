<?php

namespace Tests\Support;

use RuntimeException;

class UnparsableColorException extends RuntimeException
{
    public function __construct(string $color)
    {
        parent::__construct("Expected an oklch() colour, got [{$color}].");
    }
}
