# Transformer
transformer is a PHP package for transforming values or input, powered by the [Laravel](https://laravel.com) framework's validation components.

![Tests](https://github.com/surgiie/transformer/actions/workflows/tests.yml/badge.svg)


## Installation
```bash
composer require surgiie/transformer
````

## Usage

To use the package, pass your data and an array of callable functions that your data should be passed through:

```php
<?php

use Closure;
use Illuminate\Support\Stringable;

// Example functions available at runtime:
function to_carbon($value)
{
    return new Carbon\Carbon($value);
}

function only_numbers($value)
{
    return preg_replace("/[^0-9]/",'',$value);
}

$input = [
  'first_name'=>'    jim    ',
  'last_name'=>'   thompson',
  'address'  => '123 some street',
  'phone_number'=>'123-456-7890',
  'date_of_birth'=>"1991-05-01",
];

$transformers = [
    'first_name'=>'trim|ucfirst',
    'last_name'=>'trim|ucfirst',
    'phone_number'=>'only_numbers',
    // more on object values and method delegation below
    'address' => [Stringable::class, '->after:123 ', '->toString'],
    'date_of_birth'=>'to_carbon|->format:m/d/y',
];

$transformer = new DataTransformer($input, $transformers);

$newData = $transformer->transform();
// Returns:
// [
//     "first_name" => "Jim",
//     "last_name" => "Thompson",
//     "phone_number" => "1234567890",
//     "address"=> "some street",
//     "date_of_birth" => "05/01/91",
// ]

```
Note that the syntax is similar to the Laravel validation syntax because this package is powered by the same components.


## Passing Arguments

You can specify arguments for your functions using a `<function>:<comma-delimited-list>` syntax:

```php
<?php

$transformers = [
    'example'=>'your_function:arg1,arg2',
];

```
### Specify Value Argument Order

By default, the first argument passed to your function will be the value being formatted, followed by the arguments specified in the order provided. If your function does not accept the value as the first argument, you can use the `:value:` placeholder to specify the order. For example:

```php
<?php

$input = ['phone_number'=>'123-456-3235'];
$transformers = [
    'example'=>'preg_replace:/[^0-9]/,,:value:',
];
$transformer = new DataTransformer($input, $transformers);
$transformer->transform();
```

### Casting Arguments

You may find that when passing arguments to the transforming functions, you may hit run time errors due to typehints on arguments for basic types. Since arguments are being specified as a string here, you may come across a scenario like this:


```php
<?php

function example_function(int $value){
    return $value + 1;
}

$input = ['example'=>"1"];

$transformers = [
    'example'=>'example_function:1',
];

```

The above would throw an error because the `1` argument being passed in is a string and the function expects a integer. In these cases, you can use the following convention to cast the argument to specific native type, by suffixing `@<type>` to the value. For example to cast the `1` to an integer:


```php
<?php

function example_function(int $value){
    return $value + 1;
}

$input = ['example'=>"1"];

$transformers = [
    'example'=>'example_function:1@int', // "1" will  be casted to an int.
];

```

#### Available Casting Types:

The following basic casting types are available

- int
- str
- float
- bool
- array
- object

**Note** For more complex type or casting needs, use a Closure or `Surgiie\Transformer\Contracts\Transformable` class as documented below.

## Optional Transformation

If you only want to transform a value if it is not null or "blank", you can use the `?` character in the chain of functions to specify when to break out of processing. This is often placed at the start of the chain:

```php

<?php

$input = ['first_name'=>null];
$transformers = [
    'example'=>'?|function_one|function_two',
];
$transformer = new DataTransformer($input, $transformers);
$transformer->transform();
```
Note: This package uses Laravel's `blank` helper to determine blank/empty values. If you have more complex logic for breaking out of rules, you can use a closure or a `\Surgiie\Transformer\Contracts\Transformable` class and call the 2nd argument exit callback.

## Closures and Transformable Classes

You can use closures to transform your values:

```php

<?php

$input = ['first_name'=>' Bob'];
$transformers = [
    'first_name'=>['trim', function ($value) {
        // modify the value
        return $value;
    }],
];
$transformer = new DataTransformer($input, $transformers);
$transformer->transform();
```
Alternatively, you can implement the `Surgiie\Transformer\Contracts\Transformable` contract and use class instances:

```php

<?php

use Surgiie\Transformer\DataTransformer;
use Surgiie\Transformer\Contracts\Transformable;

class TransformValue implements Transformable
{
    public function transform($value, Closure $exit)
    {
        // quit transforming value(s)
        if($someCondition){
            $exit();
        }

        // or modify the value
        $value = "Changed";

        return $value;
    }
}

$input = ['first_name' => ' Bob'];
$transformers = [
    'first_name' => ['trim', new TransformValue],
];
$transformer = new DataTransformer($input, $transformers);
$transformer->transform();

```

## Array Input
You can also format nested array data using dot notation:

```php

<?php

$input = [
    'contact_info'=>[
        'first_name'=>'    jim    ',
        'last_name'=>'   thompson',
        'phone_number'=>'123-456-7890',
        'address'=>'123 some lane.'
    ]
];
$transformers = [
    'contact_info.first_name'=>'trim|ucwords',
    'contact_info.last_name'=>'trim|ucwords',
    'contact_info.phone_number'=>'preg_replace:/[^0-9]/,,:value:',
    'contact_info.address'=>[function ($address) {
        return 'Address Is: '.$address;
    }],
];
$transformer = new DataTransformer($input, $transformers);
$transformer->transform();
```
## Wildcards
You can also use wildcards on keys to apply transformers on keys that match the wildcard pattern:

```php

<?php

$input = [
    'first_name'=>'    jim    ',
    'last_name'=>'   thompson',
    'ignored'=>' i-will-be-the-same'
];
$transformers = [
    // apply to all keys that contain "name"
    '*name*'=>'trim|ucwords',
];
$transformer = new DataTransformer($input, $transformers);
$transformer->transform();
```

## Object Values/Method Delegation
It is possible to delegate a function call to an object if the value has been converted to an instance. Using the syntax `-><methodName>:args`, you can specify method chaining on that instance:

```php

<?php

use Closure;

class Example
{

    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function concat($string)
    {
        return $this->value . $string;
    }

}

function example($value)
{
    return new Example($value);
}


$input = [
  'string'=>"Foo",
];
$transformers = [
    'string'=>'example|->concat:Bar',
];
```
You can also use class constants that accept a single value as its constructor, for example:

```php

<?php

$input = [
    'string'=>"Foo",
];
$transformers = [
    'string'=>[Example::class, '->concat:Bar'],
];
```

## Guard Layer Over Execution

By default, all available functions that are callable at runtime will be executed. However, if you want to add a protection or security layer that prevents certain methods from being called, you can add a guard callback that checks if a method should be called by returning true:

```php

<?php

use Surgiie\Transformer\DataTransformer;
use Surgiie\Transformer\Transformer;

// accepts the function name being executed and the key/name of the input being processed:
Transformer::guard(function($method, $key){
    // only "trim" is allowed to be executed
    return in_array($method, ['trim']);
});

$input = [
    'first_name'=>'    jim    ',
];
$transformers = [
    'first_name'=>'trim|ucwords',
];

$transformer = new DataTransformer($input, $transformers);

// throws a Surgiie\Transformer\Exceptions\ExecutionNotAllowedException once it gets to ucwords due to the guard method.
$transformer->transform();
```

## Manually Transforming Values/Single Values
To format a one-off value, use the Transformer class:


```php
<?php

use Surgiie\Transformer\Transformer;

$transformer = new Transformer("   uncle bob   ", ['trim', 'ucwords']);

$transformer->transform(); // returns "Uncle Bob"
```

## Use Traits

To transform data and values on-the-fly in your classes, use the `\Surgiie\Transformer\Concerns\UsesTransformer` trait:

```php

<?php

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Surgiie\Transformer\Concerns\UsesTransformer;

class ExampleController extends Controller
{
    use UsesTransfomer;

    public function store(Request $request)
    {
        //...

        // transform a single value
        $newValue = $this->transform(" example  ", ['trim|ucwords']);
        // or transform an array of data
        $newData = $this->transformData(['example'=> 'data    '], ["example"=>'trim|ucwords']);
    }
}
```
### Use the Request Macro
To transform data using a macro on a `Illuminate\Http\Request` object instance, call the `transform()` method on the request, which returns the transformed data.


```php

public function store(Request $request)
{
    // Using data from the request object (i.e. `$request->all()`)
    $transformedData = $request->transform([
        'first_name' => ['strtoupper'],
    ]);

    // $transformedData['first_name'] will be all uppercase
    // all other data will be included from the request

    // You can also customize the input that is transformed,
    // in this case $transformedData will only have the `first_name` key.
    $transformedData = $request->transform($request->only(['first_name']), [
        'first_name' => ['strtoupper'],
    ]);
}

```
When calling on a `FormRequest` object, it uses the `validated()` function to retrieve the input data. Note that this requires the data you are targeting to be defined as a validation rule in your form request's rules function, otherwise the data will be omitted from transformation.

## Package Discovery/Don't Discover
Laravel automatically registers the package service provider, but if you don't want this, you can ignore package discovery for the service provider by including the following in your `composer.json`:

```json
"extra": {
    "laravel": {
        "dont-discover": [
            "surgiie/transformer"
        ]
    }
}
```


## Contribute

Contributions are always welcome in the following manner:

- Discussions
- Issue Tracker
- Pull Requests
