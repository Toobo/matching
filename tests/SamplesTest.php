<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Matching package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Toobo\Matching\Tests;

use PHPUnit\Framework\TestCase;
use Toobo\Matching\Matcher;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Matching
 * @license http://opensource.org/licenses/MIT MIT
 */
class SamplesTest extends TestCase
{

    /**
     * @dataProvider provideFactoryData
     * @param array $args
     * @param int $index
     * @param int $expected
     */
    public function testAsFactory(array $args, int $index, int $expected)
    {

        $factory = Matcher::for (

            function (int...$numbers) {
                return new \ArrayIterator($numbers);
            },
            function (array $numbers) {
                return new \ArrayIterator(array_values(array_filter($numbers, 'is_int')));
            },
            function ($number) {
                return is_numeric($number)
                    ? new \ArrayIterator([(int)$number])
                    : new \ArrayIterator([0]);
            },
            function () {
                return new \ArrayIterator([0]);
            }
        )->failWith(

            function () {
                return new \ArrayIterator([-1]);
            }
        );

        $iterator = $factory(...$args);

        self::assertInstanceOf(\ArrayIterator::class, $iterator);
        self::assertSame($expected, $iterator[$index]);
    }

    public function provideFactoryData()
    {
        return [
            [[0], 0, 0],
            [[1], 0, 1],
            [[1, 2, 3], 0, 1],
            [[1, 2, 3], 1, 2],
            [[1, 2, 3], 2, 3],
            [[[1, 2, 3]], 0, 1],
            [[[1, 2, 3]], 1, 2],
            [[[1, 2, 3]], 2, 3],
            [[], 0, 0],
            [['foo'], 0, 0],
            [['123'], 0, 123],
            [[123.123], 0, 123],
            [[['foo', 1, 'bar']], 0, 1],
            [['a', 1, true], 0, -1],
        ];
    }

    /**
     * @dataProvider provideEventData
     * @param \stdClass $event
     * @param string $expected_reaction
     */
    public function testAsEventMiddleware(\stdClass $event, string $expected_reaction)
    {
        /**
         * Returns event untouched, but ensure expected event reaction happens
         *
         * @param callable $react
         * @param \stdClass $event
         * @return \stdClass
         */
        $listener = function (callable $react, \stdClass $event) use ($expected_reaction) {

            self::assertSame($expected_reaction, $react(...$event->data));

            return $event;
        };

        $react = Matcher::for (

            function (int $a) {
                return 'Int';
            },
            function (string $a) {
                return 'String';
            },
            function (int $a, string $b) {
                return 'Int+String';
            },
            function (string ...$a) {
                return 'Variadic_String';
            },
            function (int ...$a) {
                return 'Variadic_Int';
            },
            function ($a) {
                return 'Unknown_1';
            },
            function ($a, $b) {
                return 'Unknown_2';
            },
            function ($a, int ...$b) {
                return 'Unknown_1+Variadic_Int';
            },
            function ($a, ...$b) {
                return 'Unknown_1+Variadic_Unknown';
            },
            function () {
                return 'None';
            }
        );

        self::assertSame($event, $listener($react, $event));
    }

    public function provideEventData()
    {
        return [
            [
                (object)['data' => [1, true]], // event
                'Unknown_2'                    // expected reaction
            ],
            [
                (object)['data' => []],
                'None'
            ],
            [
                (object)['data' => [1, 'a', 'b', '2']],
                'Unknown_1+Variadic_Unknown'
            ],
            [
                (object)['data' => ['a', 1, 2, 3]],
                'Unknown_1+Variadic_Int'
            ],
            [
                (object)['data' => [1, 'a']],
                'Int+String'
            ],
            [
                (object)['data' => [1]],
                'Int'
            ],
            [
                (object)['data' => ['1']],
                'String'
            ],
            [
                (object)['data' => ['1', 'a']],
                'Variadic_String'
            ],
            [
                (object)['data' => [1, 2, 3]],
                'Variadic_Int'
            ],
            [
                (object)['data' => [true, 'a', 'b', '2']],
                'Unknown_1+Variadic_Unknown'
            ],
            [
                (object)['data' => ['true', 'a', 'b', '2']],
                'Variadic_String'
            ],
            [
                (object)['data' => [true]],
                'Unknown_1'
            ],
        ];
    }

    public function testAsFactoryWithStatic()
    {
        $factory = Matcher::for (
            [Stubs\FactoryTarget::class, 'fromString'],
            [Stubs\FactoryTarget::class, 'fromObject'],
            [Stubs\FactoryTarget::class, 'fromArray'],
            [Stubs\FactoryTarget::class, 'fromVariadic']
        )->failWith(function () {
            return Stubs\FactoryTarget::fromString('Failed!!');
        });

        /** @var \Toobo\Matching\Tests\Stubs\FactoryTarget $fromString */
        $fromString = $factory('foo');
        static::assertInstanceOf(Stubs\FactoryTarget::class, $fromString);
        static::assertSame(['foo'], $fromString->things());

        /** @var \Toobo\Matching\Tests\Stubs\FactoryTarget $fromArray */
        $fromArray = $factory(['foo', 'bar']);
        static::assertInstanceOf(Stubs\FactoryTarget::class, $fromArray);
        static::assertSame(['foo', 'bar'], $fromArray->things());

        /** @var \Toobo\Matching\Tests\Stubs\FactoryTarget $fromVariadic */
        $fromVariadic = $factory('a', 'b', 'c');
        static::assertInstanceOf(Stubs\FactoryTarget::class, $fromVariadic);
        static::assertSame(['a', 'b', 'c'], $fromVariadic->things());

        /** @var \Toobo\Matching\Tests\Stubs\FactoryTarget $fromObject */
        $fromObject = $factory((object)['a' => 'X', 'b' => 'Y']);
        static::assertInstanceOf(Stubs\FactoryTarget::class, $fromObject);
        static::assertSame(['X', 'Y'], $fromObject->things());

        /** @var \Toobo\Matching\Tests\Stubs\FactoryTarget $fromBadVariadic */
        $fromBadVariadic = $factory('a', [], 'x');
        static::assertInstanceOf(Stubs\FactoryTarget::class, $fromBadVariadic);
        static::assertSame(['Failed!!'], $fromBadVariadic->things());
    }

    public function testAsMultiConstructor()
    {
        $fromInt = new Stubs\MultiConstructor(42);
        static::assertInstanceOf(Stubs\MultiConstructor::class, $fromInt);
        static::assertSame(42, $fromInt->number());
        static::assertSame('default', $fromInt->string());

        $fromString = new Stubs\MultiConstructor('foo');
        static::assertInstanceOf(Stubs\MultiConstructor::class, $fromString);
        static::assertSame('foo', $fromString->string());
        static::assertSame(0, $fromString->number());

        $fromArray = new Stubs\MultiConstructor(['string' => 'Hi', 'number' => 3]);
        static::assertInstanceOf(Stubs\MultiConstructor::class, $fromArray);
        static::assertSame('Hi', $fromArray->string());
        static::assertSame(3, $fromArray->number());

        $fromMixed = new Stubs\MultiConstructor('foo', 2, []);
        static::assertInstanceOf(Stubs\MultiConstructor::class, $fromMixed);
        static::assertSame('error', $fromMixed->string());
        static::assertSame(-1, $fromMixed->number());
    }
}