<?php

use Carbon\Carbon;
use Surgiie\Transformer\Contracts\Transformable;
use Surgiie\Transformer\DataTransformer;
use Surgiie\Transformer\Exceptions\NotCallableException;
use Surgiie\Transformer\Transformer;

beforeEach(function () {
    Transformer::unguard();
    $this->data = [
        'first_name' => '    jim    ',
        'last_name' => '   thompson',
        'date_of_birth' => '2020-05-24',
        'password' => 'abcdefgh12345',
        'favorite_number' => '24',
        'favorite_date' => null,
        'get_notifications' => true,
        'contact_info' => [
            'address' => '123 some lane street',
            'home_phone' => '123-456-7890',
            'cell_phone' => '123-456-7890',
            'apartment_number' => '12B',
            'email' => 'email@example.com',
        ],
    ];
});

it('calls functions on data', function () {
    // does nothing when no functions specified.
    $transformer = (new DataTransformer($this->data, []));
    $transformedData = $transformer->transform();
    expect($transformedData)->toBe($this->data);

    // otherwise calls functions.
    $transformer = (new DataTransformer($this->data, [
        'first_name' => 'trim|ucfirst',
        'favorite_number' => 'intval',
    ]));

    $transformedData = $transformer->transform();
    expect($transformedData['first_name'])->toBe('Jim');
    expect($transformedData['favorite_number'])->toBe(24);
    expect($transformedData['first_name'])->not->toBe($this->data['first_name']);
});

it('can use class constants', function () {
    $formatter = (new DataTransformer($this->data, ['date_of_birth' => [
        'trim',
        Carbon::class,
    ]]));

    $data = $formatter->transform();
    expect($data['date_of_birth'])->toBeInstanceOf(Carbon::class);
});

it('throws exception when non callable is called', function () {
    expect(function () {
        $transformer = (new DataTransformer($this->data, ['first_name' => 'im_not_a_callable_function']));
        $transformer->transform();
    })->toThrow(NotCallableException::class);
});

it('can specify value order', function () {
    $formatter = (new DataTransformer($this->data, [
        'password' => 'trim|preg_replace:/[^0-9]/,,:value:',
    ]));

    $formattedData = $formatter->transform();
    expect($formattedData['password'])->toBe('12345');
    expect($formattedData['password'])->not->toBe($this->data['password']);
});

it('can process callbacks', function () {
    $formatter = (new DataTransformer($this->data, [
        'get_notifications' => function () {
            return 'Never';
        },
    ]));

    $formattedData = $formatter->transform();

    expect($formattedData['get_notifications'])->toBe('Never');

    expect($formattedData['get_notifications'])->not->toBe($this->data['get_notifications']);
});

it('can process tranformable objects', function () {
    $formatter = (new DataTransformer($this->data, [
        'get_notifications' => new class() implements Transformable
        {
            public function transform($value, Closure $exit)
            {
                return 'Yes';
            }
        },
    ]));

    $formattedData = $formatter->transform();

    expect($formattedData['get_notifications'])->toBe('Yes');

    expect($formattedData['get_notifications'])->not->toBe($this->data['get_notifications']);
});

it('can exit on blank input using ?', function () {
    $formatter = (new DataTransformer($this->data, [
        'favorite_date' => '?|Carbon\Carbon|.format:m/d/Y',
    ]));

    $formattedData = $formatter->transform();

    expect($formattedData['favorite_date'])->toBe($this->data['favorite_date']);
    expect($formattedData['favorite_date'])->not->toBe((new Carbon())->format('m/d/Y'));

    //assert ? break works at random position in chain.
    $this->data['favorite_date'] = '2022-05-24';
    $formatter = (new DataTransformer($this->data, [
        'favorite_date' => ['Carbon\Carbon', function () {
            return null;
        }, '?', '.format:m/d/Y'],
    ]));

    $formattedData = $formatter->transform();
    expect($formattedData['favorite_date'])->toBeNull();
    expect($formattedData['favorite_date'])->not->toBe('05/24/2022');
});

it('can delegate to underlying objects', function () {
    $formatter = (new DataTransformer($this->data, [
        'date_of_birth' => 'trim|Carbon\Carbon|->addDays:1|->format:m/d/Y',
    ]));

    $formattedData = $formatter->transform();

    expect($formattedData['date_of_birth'])->not->toBe($this->data['date_of_birth']);
    expect($formattedData['date_of_birth'])->toBe('05/25/2020');
});

it('can process wildcards on data', function () {
    $transformer = (new DataTransformer($this->data, [
        '*name*' => 'trim|ucfirst',
    ]));

    $transformedData = $transformer->transform();
    expect($transformedData['first_name'])->toBe('Jim');
    expect($transformedData['last_name'])->toBe('Thompson');
});

it('can process nested arrays with dot notation', function () {
    $formatter = (new DataTransformer($this->data, [
        'contact_info.address' => [\Illuminate\Support\Stringable::class, '->after:123 ', '->toString'],
        'contact_info.home_phone' => 'preg_replace:/[^0-9]/,,:value:',
        'contact_info.cell_phone' => 'preg_replace:/[^0-9]/,,:value:',
        'contact_info.apartment_number' => 'str_replace:B,A,:value:',
        'contact_info.email' => 'str_replace:example,gmail,:value:',

    ]));

    $formattedData = $formatter->transform();

    expect($formattedData['contact_info'])->toBe(
        [
            'address' => 'some lane street',
            'home_phone' => '1234567890',
            'cell_phone' => '1234567890',
            'apartment_number' => '12A',
            'email' => 'email@gmail.com',
        ]
    );
});

it('can process wildcards on nested arrays', function () {
    $formatter = (new DataTransformer($this->data, [
        'contact_info.*phone*' => 'preg_replace:/[^0-9]/,,:value:',
    ]));

    $formattedData = $formatter->transform();

    expect($formattedData['contact_info']['home_phone'])->toBe('1234567890');
    expect($formattedData['contact_info']['cell_phone'])->toBe('1234567890');
});
