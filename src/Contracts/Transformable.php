<?php

namespace Surgiie\Transformer\Contracts;

use Closure;

interface Transformable
{
    /**
     * Transform the given value or exit.
     */
    public function transform($value, Closure $exit);
}
