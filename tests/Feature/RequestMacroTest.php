<?php

use Surgiie\Transformer\Transformer;

beforeEach(function () {
    Transformer::unguard();
    // This is kind of a dirty way to add the macro
    // without including orchestra/testbench
    (new \Surgiie\Transformer\TransformerServiceProvider(''))
        ->boot();

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
            'home_phone' => '1234567890',
            'cell_phone' => '1234567890',
            'apartment' => '12A',
            'email' => 'email@example.com',
        ],
    ];

    $this->request = (new \Illuminate\Http\Request())
        ->merge($this->data);
});

it('calls transform on manual data', function () {
    $transformedData = $this->request->transform(
        ['not_included_in_request' => '    jim    '],
        ['not_included_in_request' => 'trim|ucfirst']
    );

    expect(count($transformedData))->toBe(1)
        ->and($transformedData['not_included_in_request'])->toBe('Jim');
});

it('calls transform on request data and includes everything', function () {
    $transformedData = $this->request->transform(
        ['first_name' => 'trim|ucfirst']
    );

    expect(count($transformedData))->toBe(count($this->request->all()))
        ->and($transformedData['first_name'])->toBe('Jim');
});

it('can specify value order', function () {
    $formattedData = $this->request->transform([
        'password' => 'trim|preg_replace:/[^0-9]/,,:value:',
    ]);
    expect($formattedData['password'])->toBe('12345')
        ->and($formattedData['password'])->not->toBe($this->data['password']);
});

it('can process callbacks', function () {
    $formattedData = $this->request->transform([
        'get_notifications' => function () {
            return 'Never';
        },
    ]);

    expect($formattedData['get_notifications'])->toBe('Never')
        ->and($formattedData['get_notifications'])->not->toBe($this->data['get_notifications']);
});

it('can use inline function and delegate', function () {
    function to_carbon($value)
    {
        return new \Carbon\Carbon($value);
    }
    $formattedData = $this->request->transform([
        'date_of_birth' => 'to_carbon|->addDay:1@int|->format:m/d/Y',
    ]);

    expect($formattedData['date_of_birth'])->toBe('05/25/2020');
});

it('can use the validated data from form requests', function () {
    $this->request = (new \Surgiie\Transformer\Tests\SampleFormRequest())
        ->merge($this->data);

    $transformedData = $this->request->transform(['first_name' => 'trim|ucfirst']);

    expect(count($transformedData))->toBe(1)
        ->and($transformedData['first_name'])->toBe('Jim');
});

it('can process nested arrays with dot notation', function () {
    $transformedData = $this->request->transform([
        'contact_info.address' => [\Illuminate\Support\Stringable::class, '->after:123 ', '->toString'],
        'contact_info.home_phone' => 'preg_replace:/[^0-9]/,,:value:',
        'contact_info.cell_phone' => 'preg_replace:/[^0-9]/,,:value:',
        'contact_info.apartment' => 'str_replace:B,A,:value:',
        'contact_info.email' => 'str_replace:example,gmail,:value:',
    ]);

    expect($transformedData['contact_info'])->toBe(
        [
            'address' => 'some lane street',
            'home_phone' => '1234567890',
            'cell_phone' => '1234567890',
            'apartment' => '12A',
            'email' => 'email@gmail.com',
        ]
    );
});

it('can process wildcards on nested arrays', function () {
    $formattedData = $this->request->transform([
        'contact_info.*phone*' => 'preg_replace:/[^0-9]/,,:value:',
    ]);

    expect($formattedData['contact_info']['home_phone'])->toBe('1234567890');
    expect($formattedData['contact_info']['cell_phone'])->toBe('1234567890');
});
