<?php

namespace Surgiie\Transformer\Contracts;

use Closure;

/**
 * Contract for custom transformer classes.
 *
 * Classes implementing this interface can be used as custom transformers
 * within the transformation chain.
 */
interface Transformable
{
    /**
     * Transform the given value or exit.
     *
     * @param  mixed  $value  The value to transform
     * @param  Closure  $exit  The callback to abort transformation
     * @return mixed The transformed value
     */
    public function transform($value, Closure $exit);
}
