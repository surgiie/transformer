<?php

namespace Surgiie\Transformer\Concerns;

use Surgiie\Transformer\DataTransformer;
use Surgiie\Transformer\Transformer;

trait UsesTransformer
{
    /**Return a new transformer instance.*/
    public function transformer($value = '', array $transformers = [], string|null $name = null): Transformer
    {
        return new Transformer($value, $transformers, $name);
    }

    /**Return a new transformer instance.*/
    public function dataTransformer(array $data = [], array $functions = []): DataTransformer
    {
        return new DataTransformer($data, $functions);
    }

    /**Transform the given value.*/
    public function transform($value = '', array $functions = [], string|null $name = null)
    {
        return $this->transformer($value, $functions, $name)->transform();
    }

    /**Transform the given data.*/
    public function transformData(array $data = [], array $functions = []): array
    {
        return $this->dataTransformer($data, $functions)->transform();
    }
}
