# Transformer

A PHP package for transforming data and values with a simple, expressive syntax. Powered by [Laravel](https://laravel.com) framework components.

![Tests](https://github.com/surgiie/transformer/actions/workflows/tests.yml/badge.svg)

## Installation

```bash
composer require surgiie/transformer
```

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
The syntax is similar to Laravel's validation syntax, as this package is powered by the same framework components.


## Passing Arguments

Pass arguments to your transformer functions using the `<function>:<comma-delimited-list>` syntax:

```php
<?php

$transformers = [
    'example'=>'your_function:arg1,arg2',
];

```
### Specifying Value Argument Position

By default, the value being transformed is passed as the first argument to your function, followed by any additional arguments. If your function expects the value in a different position, use the `:value:` placeholder:

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

When passing arguments to transformer functions, you may encounter runtime errors due to type hints. Since arguments are specified as strings, you can cast them to specific types using the `@<type>` suffix:


```php
<?php

function example_function(int $value) {
    return $value + 1;
}

$input = ['example' => '1'];

// This will throw an error because '1' is a string, not an integer
$transformers = [
    'example' => 'example_function:1',
];

// Fix by casting the argument to an integer
$transformers = [
    'example' => 'example_function:1@int',
];
```

#### Available Casting Types

- `int` - Integer
- `str` - String
- `float` - Float
- `bool` - Boolean
- `array` - Array
- `object` - Object

**Note:** For complex type casting needs, use a Closure or `Surgiie\Transformer\Contracts\Transformable` class (see below).

## Optional Transformation

To transform a value only when it's not null or blank, use the `?` character in your transformation chain. This is typically placed at the start:

```php

<?php

$input = ['first_name'=>null];
$transformers = [
    'example'=>'?|function_one|function_two',
];
$transformer = new DataTransformer($input, $transformers);
$transformer->transform();
```

**Note:** This uses Laravel's `blank()` helper to determine empty values. For complex conditional logic, use a Closure or `\Surgiie\Transformer\Contracts\Transformable` class with the exit callback.

## Closures and Transformable Classes

Use closures for custom transformation logic:

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

Alternatively, implement the `Surgiie\Transformer\Contracts\Transformable` contract for reusable transformation classes:

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

## Nested Array Data

Transform nested array data using dot notation:

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

Apply transformers to multiple keys using wildcard patterns:

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

## Object Method Delegation

When a value is transformed into an object instance, you can chain method calls using the `-><methodName>:args` syntax:

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

You can also instantiate classes directly using class constants (the value will be passed to the constructor):

```php

<?php

$input = [
    'string'=>"Foo",
];
$transformers = [
    'string'=>[Example::class, '->concat:Bar'],
];
```

## Guard Layer

Add a security layer to control which functions can be executed during transformation. Register a guard callback that returns `true` to allow a function to execute:

```php

<?php

use Surgiie\Transformer\DataTransformer;
use Surgiie\Transformer\Transformer;

// The guard receives the function name and the input key being processed
Transformer::guard(function($method, $key) {
    // Only allow 'trim' to be executed
    return in_array($method, ['trim']);
});

$input = [
    'first_name'=>'    jim    ',
];
$transformers = [
    'first_name'=>'trim|ucwords',
];

$transformer = new DataTransformer($input, $transformers);

// Throws ExecutionNotAllowedException when attempting to execute 'ucwords'
$transformer->transform();
```

## Transforming Single Values

Transform individual values using the `Transformer` class:


```php
<?php

use Surgiie\Transformer\Transformer;

$transformer = new Transformer("   uncle bob   ", ['trim', 'ucwords']);

$transformer->transform(); // returns "Uncle Bob"
```

## Using the Transformer Trait

Transform data on-the-fly in your classes by using the `\Surgiie\Transformer\Concerns\UsesTransformer` trait:

```php

<?php

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Surgiie\Transformer\Concerns\UsesTransformer;

class ExampleController extends Controller
{
    use UsesTransformer;

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
### Using the Request Macro

Transform request data using the `transform()` macro available on `Illuminate\Http\Request` instances:


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

## Disabling Auto-Discovery

Laravel automatically registers the package service provider. If you want to disable auto-discovery, add the following to your `composer.json`:

```json
"extra": {
    "laravel": {
        "dont-discover": [
            "surgiie/transformer"
        ]
    }
}
```


## Contributing

Contributions are welcome! You can contribute through:

- **Discussions** - Share ideas and ask questions
- **Issues** - Report bugs or request features
- **Pull Requests** - Submit code improvements
