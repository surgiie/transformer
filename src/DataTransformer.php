<?php

namespace Surgiie\Transformer;

use Illuminate\Support\Arr;
use Surgiie\Transformer\Concerns\UsesTransformer;

class DataTransformer
{
    use UsesTransformer;

    /**
     * The data being transformed.
     */
    protected array $data;

    /**
     * The callable functions to apply.
     */
    protected array $functions;

    /**
     * Construct a new DataTransfomer instance.
     */
    public function __construct(array $data = [], array $functions = [])
    {
        $this->setData($data);
        $this->setFunctions($functions);
    }

    /**Set the data to transform.*/
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**Set the transformers to apply on the data.*/
    public function setFunctions(array $functions): static
    {
        $this->functions = $functions;

        return $this;
    }

    /**
     * Create a new DataTransformer instance.
     */
    public static function create($data, $callables): DataTransformer
    {
        return new static($data, $callables);
    }

    /**
     * Apply the given transformers to the given data key.
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

    /**Get a new rule parser for transformers.*/
    protected function parser(array $data)
    {
        return new TransformerRuleParser($data);
    }

    /**
     * Transform the set data and return it.
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
