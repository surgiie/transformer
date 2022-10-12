<?php

namespace Surgiie\Transformer;

use Closure;
use Surgiie\Transformer\Contracts\Transformable;
use Surgiie\Transformer\Exceptions\AbortedTransformationException;
use Surgiie\Transformer\Exceptions\ExecutionNotAllowedException;
use Surgiie\Transformer\Exceptions\NotCallableException;

class Transformer
{
    /**
     * The value being transformed.
     */
    protected $value;

    /**
     * The name of the value/input being transformed.
     */
    protected ?string $name = null;

    /**
     * The callable transformer functions.
     */
    protected array $functions;

    /**The callback that determines if a transformer is allowed.*/
    protected static ?Closure $guardWith = null;

    /**
     * Construct a new s instance.
     */
    public function __construct($value = '', array|string $functions = [], string $name = null)
    {
        $this->setValue($value);
        $this->setName($name);
        $this->setFunctions($functions);
    }

    /**
     * Call transformer function using the given value and parameters.
     */
    protected function call($method, $value, array $args = [])
    {
        //first prepare the arguments for the function call.
        $args = $this->prepareArguments($value, $method, $args);

        // prep the me$method for execution if needed.
        $function = $this->prepareTransformerFunction($method);

        // check if the transformer method should be delegated to on an
        // underlying object this is done using a ".<method>" convention.
        // this allows for transforming to be delegated to 3rd party classes such
        // as carbon. e.g to_carbon|.format:m/d/Y
        if (is_object($value) && $this->shouldDelegateTransformer($function)) {
            $function = ltrim($function, '->');

            return $value->{$function}(...$args);
        }

        if (is_string($function) && class_exists($function)) {
            return new $function($value);
        }
        //check if its a custom formattable class
        if ($function instanceof Transformable) {
            return $function->transform($value, $this->abortTransormationCallback());
        // or a callback
        } elseif ($function instanceof Closure) {
            return $function($value, $this->abortTransormationCallback());
        }
        //otherwise check if the method is function and whitelisted.
        elseif (! is_callable($function)) {
            throw new NotCallableException(
                "Function $function not callable.",
            );
        } elseif (! $this->shouldExecute($function)) {
            throw new ExecutionNotAllowedException("Function $function is not allowed to be called.");
        }

        return $function(...$args);
    }

    /**Set the name of the value/input being transformed.*/
    protected function setName(null|string $name = null): static
    {
        $this->name = $name;

        return $this;
    }

    /*
     * Check if the given transformer is allowed/not guarded.
     */
    protected function shouldExecute($method): bool
    {
        if ($method instanceof Transformable || $method instanceof Closure) {
            return true;
        }

        $guard = Transformer::getGuardWith();

        return filter_var($guard($method, $this->value, $this->name), FILTER_VALIDATE_BOOL);
    }

    /**
     * Register the callback that determines execution of transformers.
     */
    public static function guard(Closure $callback): void
    {
        static::$guardWith = $callback;
    }

    /**
     * Unregister the callback that guards execution of transformers.
     */
    public static function unguard(): void
    {
        static::$guardWith = null;
    }

    /**Get the guard callback.*/
    public static function getGuardWith(): Closure
    {
        if (is_null(static::$guardWith)) {
            return fn () => true;
        }

        return static::$guardWith;
    }

    /**
     * Check if the transformer method starts with a ->, i.e "-><method>" format.
     * This signifies that we want to deletage/method chain to the set value which
     * can be a either 3rd party package or a developer custom class instance.
     */
    protected function shouldDelegateTransformer($method): bool
    {
        if (! is_string($method)) {
            return false;
        }

        return str_starts_with(trim($method), '->');
    }

    /**
     * Return a callback for passing closures/transformable classes
     * that throws an exception for aborting processing of tranformers.
     */
    protected function abortTransormationCallback(): Closure
    {
        return function () {
            throw new AbortedTransformationException();
        };
    }

    /**Set the value to transform.*/
    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**Set the transformers to apply on the data.*/
    public function setFunctions(array|string $functions): static
    {
        if (is_string($functions)) {
            $functions = explode('|', $functions);
        }

        $this->functions = $functions;

        return $this;
    }

    /**
     * Prepare the transformer function for execution.
     */
    protected function prepareTransformerFunction($tranformer)
    {
        if (is_string($tranformer) && is_subclass_of($tranformer, Transformable::class)) {
            $tranformer = new $tranformer();
        }

        return $tranformer;
    }

    /**
     * Prepare arguments for a function call on the value.
     */
    protected function prepareArguments($value, $function, array $args = [])
    {
        //delegated function calls do not get the value passed in by default
        //since the set value is the transformer function itself,
        // the value parameter is is not needed as a param.
        if (is_object($value) && $this->shouldDelegateTransformer($function)) {
            $defaults = [];
        } else {
            $defaults = [$value];
        }

        $parameters = array_merge($defaults, $args);

        foreach ($parameters as $index => $param) {
            if (is_string($param) && trim($param) === ':value:') {
                $parameters[$index] = $value;
                array_shift($parameters);
                break;
            }
        }

        return $parameters;
    }

    /**
     * Apply the set transformer function on the value.
     */
    public function transform()
    {
        foreach ($this->functions as $function) {
            [$rule, $parameters] = TransformerRuleParser::parse($function);

            //if rule is ? then check if
            //the value is blank break out if so.
            if ($rule == '?') {
                if (blank($this->value)) {
                    break;
                } else {
                    continue;
                }
            }

            try {
                $this->value = $this->call($rule, $this->value, $parameters);
            } catch (AbortedTransformationException $e) {
                break;
            }
        }

        return $this->value;
    }
}
