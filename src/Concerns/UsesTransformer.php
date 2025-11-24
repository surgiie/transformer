<?php

namespace Surgiie\Transformer\Concerns;

use Surgiie\Transformer\DataTransformer;
use Surgiie\Transformer\Transformer;

/**
 * Trait for adding transformation capabilities to any class.
 *
 * This trait provides convenient methods for creating and using transformers
 * without directly instantiating the Transformer or DataTransformer classes.
 */
trait UsesTransformer
{
    /**
     * Return a new Transformer instance.
     *
     * @param  mixed  $value  The value to transform
     * @param  array<int, mixed>  $transformers  The transformation functions
     * @param  string|null  $name  Optional name for the value
     */
    public function transformer($value = '', array $transformers = [], ?string $name = null): Transformer
    {
        return new Transformer($value, $transformers, $name);
    }

    /**
     * Return a new DataTransformer instance.
     *
     * @param  array<string, mixed>  $data  The data to transform
     * @param  array<string, mixed>  $functions  The transformation functions
     */
    public function dataTransformer(array $data = [], array $functions = []): DataTransformer
    {
        return new DataTransformer($data, $functions);
    }

    /**
     * Transform the given value.
     *
     * @param  mixed  $value  The value to transform
     * @param  array<int, mixed>  $functions  The transformation functions
     * @param  string|null  $name  Optional name for the value
     * @return mixed The transformed value
     */
    public function transform($value = '', array $functions = [], ?string $name = null)
    {
        return $this->transformer($value, $functions, $name)->transform();
    }

    /**
     * Transform the given array data.
     *
     * @param  array<string, mixed>  $data  The data to transform
     * @param  array<string, mixed>  $functions  The transformation functions
     * @return array<string, mixed> The transformed data
     */
    public function transformData(array $data = [], array $functions = []): array
    {
        return $this->dataTransformer($data, $functions)->transform();
    }
}
