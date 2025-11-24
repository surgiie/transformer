<?php

namespace Surgiie\Transformer;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel service provider for the Transformer package.
 *
 * This provider registers the transform() macro on the Request class,
 * enabling convenient transformation of request data.
 */
class TransformerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Request::macro('transform', function (array $input, ?array $transformers = null): array {
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
