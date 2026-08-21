<?php

namespace Tests\Support;

use RuntimeException;

class StylesheetBlockNotFoundException extends RuntimeException
{
    public function __construct(string $selector)
    {
        parent::__construct("Could not find the [{$selector}] block in the stylesheet.");
    }
}
