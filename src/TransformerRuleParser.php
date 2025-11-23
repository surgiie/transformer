<?php

namespace Surgiie\Transformer;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationRuleParser;
use Surgiie\Transformer\Contracts\Transformable;

class TransformerRuleParser extends ValidationRuleParser
{
    /**
     * Parse an array based transformer.
     */
    protected static function parseArrayRule(array $transformer)
    {
        return [trim(Arr::get($transformer, 0)), array_slice($transformer, 1)];
    }

    /**
     * Parse a string based transformers.
     */
    protected static function parseStringRule($transformers)
    {
        $parameters = [];

        if (strpos($transformers, ':') !== false) {
            [$transformers, $parameter] = explode(':', $transformers, 2);

            $parameters = static::parseParameters($transformers, $parameter);
        }

        return [trim($transformers), $parameters];
    }

    /**
     * Prepare the given transformer for parsing.
     */
    protected function prepareRule($transformer, $attribute)
    {
        if (is_string($transformer) || $transformer instanceof Transformable || $transformer instanceof Closure) {
            return $transformer;
        }

        return parent::prepareRule($transformer, $attribute);
    }

    /**
     * Extract the transformer name and parameters from a transformer.
     *
     * @param  array|string|Transformable|Closure  $transformers
     * @return array
     */
    public static function parse($transformers)
    {
        if ($transformers instanceof Transformable || $transformers instanceof Closure) {
            return [$transformers, []];
        }

        return parent::parse($transformers);
    }
}
