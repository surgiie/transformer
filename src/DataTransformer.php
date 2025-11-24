<?php

namespace Surgiie\Transformer;

use Illuminate\Support\Arr;
use Surgiie\Transformer\Concerns\UsesTransformer;

/**
 * Transform multiple values in an array dataset with dot notation support.
 *
 * This class extends transformation capabilities to work with arrays of data,
 * supporting dot notation for nested arrays and wildcard patterns for matching
 * multiple keys at once.
 *
 * @phpstan-consistent-constructor
 */
class DataTransformer
{
    use UsesTransformer;

    /**
     * The data being transformed.
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * The callable functions to apply.
     *
     * @var array<string, mixed>
     */
    protected array $functions;

    /**
     * Construct a new DataTransformer instance.
     *
     * @param  array<string, mixed>  $data  The data to transform
     * @param  array<string, mixed>  $functions  The transformation functions to apply
     */
    public function __construct(array $data = [], array $functions = [])
    {
        $this->setData($data);
        $this->setFunctions($functions);
    }

    /**
     * Set the data to transform.
     *
     * @param  array<string, mixed>  $data  The data to set
     * @return void
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Set the transformers to apply on the data.
     *
     * @param  array<string, mixed>  $functions  The transformation functions
     * @return $this
     */
    public function setFunctions(array $functions): static
    {
        $this->functions = $functions;

        return $this;
    }

    /**
     * Create a new DataTransformer instance.
     *
     * @param  array<string, mixed>  $data  The data to transform
     * @param  array<string, mixed>  $callables  The transformation functions
     */
    public static function create($data, $callables): static
    {
        return new static($data, $callables);
    }

    /**
     * Apply the given transformers to the given data key.
     *
     * @param  Transformer  $transformer  The transformer instance to use
     * @param  string  $key  The data key to transform
     * @param  array<int, mixed>  $functions  The transformation functions
     * @return $this
     */
    protected function applyTransformers(Transformer $transformer, string $key, array $functions): static
    {
        if (! Arr::has($this->data, $key)) {
            return $this;
        }

        $transformer->setValue(Arr::get($this->data, $key));
        $transformer->setFunctions($functions);

        Arr::set($this->data, $key, $transformer->transform());

        return $this;
    }

    /**
     * Get a new rule parser for transformers.
     *
     * @param  array<string, mixed>  $data  The data to parse
     * @return TransformerRuleParser
     */
    protected function parser(array $data)
    {
        return new TransformerRuleParser($data);
    }

    /**
     * Transform the set data and return it.
     *
     * @return array<string, mixed> The transformed data
     */
    public function transform(): array
    {
        $parsed = $this->parser($this->data)->explode($this->functions);

        $transformer = $this->transformer();
        foreach ($parsed->rules as $key => $functions) {
            $this->applyTransformers($transformer, $key, $functions);
        }

        return $this->data;
    }
}
