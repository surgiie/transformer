# transformer

![Tests](https://github.com/surgiie/transformer/actions/workflows/tests.yml/badge.svg)

## Introduction

`transformer` is a php package for transforming values or input. Powered by the [Laravel](https://laravel.com) framework's validation components.

## Install

`composer require surgiie/transformer`

## Use

The most basic use is simple, just pass your data and array of callable functions that your data should be called against:


```php
<?php

use Closure;

// example available functions at runtime:
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
  'phone_number'=>'123-456-7890',
  'date_of_birth'=>"1991-05-01",
];

$transformers = [
    'first_name'=>'trim|ucfirst',
    'last_name'=>'trim|ucfirst',
    'phone_number'=>'only_numbers',
    'date_of_birth'=>'to_carbon|->format:m/d/y', // more on "object values and method chaining below:"
];

$transformer = new DataTransformer($input, $transformers);

$newData = $transformer->transform();
// Returns:
// [
//     "first_name" => "Jim",
//     "last_name" => "Thompson",
//     "phone_number" => "1234567890",
//     "date_of_birth" => "05/01/91",
// ]

```

Notice that the syntax is very similiar to the [laravel validation](https://laravel.com/docs/9.x/validation) syntax.
Again, this is because this package is powered by the same components, so when writing code that is combined with validation, the syntax and code is consistent and fluent.

### Passing Arguments/Specifying Value Argument Order
Arguments can be specified to your functions using a `<function>:<comma-delimited-list>` syntax. An example:

```php

$transformers = [
    'example'=>'your_function:arg1,arg2',
];
```
By default, your function will be passed the value being formatted as the first argument then will pass the arguments in the order you specify them. However, if your function does not
accept the value as the first argument, you may use the `:value:` placeholder to specify order. For example, `preg_replace` accepts the value to change as the 3rd argument:

```php

$input = ['phone_number'=>'123-456-3235'];
$transformers = [
    'example'=>'preg_replace:/[^0-9]/,,:value:',
];

$transformer = new DataTransformer($input, $transformers);
$transformer->transform();

```
### Optional Transformation/Blank Input
Sometimes you may only want to transform a value if the value isnt null or "blank": You can specify `?` anywhere in the chain of functions to specify if the we should break out of processing functions, often this should be defined in front of all your functions:

```php
$input = ['first_name'=>null];

$transformers = [
    'example'=>'?|function_one|function_two',
];
// no functions will be processed because first_name is null.
$transformer = new DataTransformer($input, $transformers);
$transformer->transform();
```

**Note:** This packages uses Laravel's [blank](https://laravel.com/docs/8.x/helpers#method-blank) helper to determine blank/empty values. If you have more complicated logic to break out of rules, use a closure or a `\Surgiie\Transformer\Contracts\Transformable` class and call the 2nd argument exit callback:

### Closures/Transformable Classes
You can use closures for transforming your value as well:

```php

$input = ['first_name'=>' Bob'];
$transformers = [
    'first_name'=>['trim', function ($value) {
        // change the value.
        return $value;
    }],
]

$transformer = new DataTransformer($input, $transformers);
$transformer->transform();

```
Or you can also implement the `Surgiie\Transformer\Contracts\Transformable` contract and use class instances:

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
            $exit(); // equivalent to ? documented above
        }

        // or change the $value
        $value = "Changed";

        return $value;
    }
}

$input = ['first_name'=>' Bob'];
$transformers = [
    'first_name'=>['trim', new TransformValue],
];
$transformer = new DataTransformer($input, $transformers);
$transformer->transform();
```



### Array Input

You may also format nested array data using dot notation:

```php

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


### Wildcards

Wildcards are also supported, for applying functions on keys that match a wildcard pattern:

```php
<?php

$input = [
    'first_name'=>'    jim    ',
    'last_name'=>'   thompson',
    'ignored'=>' i-will-be-the-same'
];
$transformers = [
    //apply to all keys that contain "name"
    '*name*'=>'trim|ucwords',
];

$transformer = new DataTransformer($input, $transformers);
$transformer->transform();
```


### Object Values/Method Delegation

In our first example above, we used an example of passing a value that creates [Carbon](https://carbon.nesbot.com/docs/) instance then calls the `format` method on that instance.

It is possible to delegate a function call to the value if it has been converted to instance. Using a `-><methodName>` convention you can specify method chaining on that instance:

```php

<?php

use Closure;

// example available functions at runtime:
function to_carbon($value)
{
    return new Carbon\Carbon($value);
}

$input = [
  'some_date'=>"1991-05-01",
];

$transformers = [
    'some_date'=>'to_carbon|->addDay:1|->format:m/d/y',
];

```

It is also possible to use class constants that accept a single value as it's constructor, for example, the above example, can also be written as:

```php

<?php

$input = [
  'some_date'=>"1991-05-01",
];

$transformers = [
    'some_date'=>[Carbon\Carbon::class, '->addDay:1', '->format:m/d/y'],
];

```


### Guard Layer Over Execution

By default, all available functions that are callable at runtime will be executed but if you have concerns about this or want to add a protection/security layer that prevents certain methods from being called, you may easily add a guard callback that checks if a method should be called by returning true:

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

### Manually Transforming Values/Single Values

If you have a simple one off value to format, you can use the `Transformer` class manually:

```php
<?php


use Surgiie\Transformer\Transformer;

$transformer = new Transformer("   uncle bob   ", ['trim', 'ucwords']);

$transformer->transform(); // returns "Uncle Bob"

```


### Use Traits

If you want to transform data and values on the fly quickly in your classes, you can utilize the `\Surgiie\Transformer\Concerns\UsesTransformer` concerns.

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
        // single one off value

        $newValue = $this->transform(" example  ", ['trim|ucwords'])

        $newData = $this->transformData($request->validated(), ["example"=>'trim|ucwords']);

    }
}

```

## Contribute

Contributions are always welcome in the following manner:
- Issue Tracker
- Pull Requests
