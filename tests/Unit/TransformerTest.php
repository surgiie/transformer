<?php

use Carbon\Carbon;
use Surgiie\Transformer\Exceptions\ExecutionNotAllowedException;
use Surgiie\Transformer\Exceptions\NotCallableException;
use Surgiie\Transformer\Transformer;

beforeEach(function () {
    Transformer::unguard();
});

it('calls functions on values', function () {
    $transformer = (new Transformer('   uncle bob  ', [
        'trim',
        'ucwords',
    ]));
    expect($transformer->transform())->toBe('Uncle Bob');
});

it('calls functions only if specified', function () {
    $formatter = (new Transformer($value = '   uncle bob  ', []));
    expect($formatter->transform())->toBe($value);
});

it('throws exception when non callable is called', function () {
    expect(function () {
        $formatter = (new Transformer('   uncle bob  ', ['im_not_a_callable_function']));
        $formatter->transform();
    })->toThrow(NotCallableException::class);
});

it('can specify value order', function () {
    $formatter = (new Transformer('   12345Abc', 'trim|preg_replace:/[^0-9]/,,:value:'));

    expect($formatter->transform())->toBe('12345');
});

it('will ignore blank input with ?', function () {
    $formatter = (new Transformer(null, '?|trim|preg_replace:/[^0-9]/,,:value:'));

    expect($formatter->transform())->toBeNull();
});

it('can process callbacks', function () {
    $formatter = (new Transformer('   12345Abc', [
        'trim',
        function ($value) {
            return preg_replace('/[^0-9]/', '', $value);
        },
    ]));

    expect($formatter->transform())->toBe('12345');
});

it('can use class constants', function () {
    $formatter = (new Transformer('   2020-05-24  ', [
        'trim',
        Carbon::class,
    ]));

    expect($formatter->transform())->toBeInstanceOf(Carbon::class);
});

it('can delegate to underlying value instances', function () {
    $formatter = (new Transformer('   2020-05-24  ', [
        'trim',
        Carbon::class,
        '->addDays:1',
        '->format:m/d/Y',
    ]));

    expect($formatter->transform())->toBe('05/25/2020');
});

it('throws exception when guarded', function () {
    expect(function () {
        Transformer::guard(function ($method) {
            return in_array($method, ['trim']);
        });

        $formatter = (new Transformer('   uncle bob  ', [
            'trim',
            'ucwords',
        ]));

        $formatter->transform();
    })->toThrow(ExecutionNotAllowedException::class, 'Function ucwords is not allowed to be called.');
});
