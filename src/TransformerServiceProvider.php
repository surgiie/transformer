<?php

namespace Surgiie\Transformer;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class TransformerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Request::macro('transform', function (array $input, array $transformers = null): array {
            $localTransformers = $transformers ?? $input;
            $input = is_null($transformers)
                ? (method_exists($this, 'validated')
                    ? $this->validated()
                    : $this->all())
                : $input;

            return (new DataTransformer($input, $localTransformers))
                ->transform();
        });
    }
}
