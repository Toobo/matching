<?php declare(strict_types=1); # -*- coding: utf-8 -*-
/*
 * This file is part of the Toobo Matching package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Toobo\Matching\Tests;

use Toobo\Matching\Exception\InvalidBindTarget;
use Toobo\Matching\Exception\NotMatched;
use Toobo\Matching\Matcher;
use PHPUnit\Framework\TestCase;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Toobo\Matching\Tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class MatcherTest extends TestCase
{

    /**
     * @dataProvider provideMatcherData
     * @param array $arguments
     * @param $expected
     */
    public function testMatch(array $arguments, $expected)
    {
        $matcher = Matcher::for (
            function (string $string, $a, $b) {
                return 'string $string, $a, $b';
            },
            function (string $string, $a, \Traversable $traversable) {
                return 'string $string, $a, Traversable $traversable';
            },
            function (string $string, int $integer, \Traversable $traversable) {
                return 'string $string, int $integer, Traversable $traversable';
            },
            function (string $string) {
                return 'string $string';
            },
            function (int $integer) {
                return 'int $integer';
            },
            function (string $string, int $integer) {
                return 'string $string, int $integer';
            },
            function ($a, $b, ...$cc) {
                return '$a, $b, ...$cc';
            },
            function ($a, $b, array...$cc) {
                return '$a, $b, array...$cc';
            },
            function ($a, ...$bb) {
                return '$a, ...$bb';
            },
            function (\ArrayAccess $array_access) {
                return 'ArrayAccess $array_access';
            },
            function (...$aa) {
                return '...$aa';
            },
            function ($a) {
                return '$a';
            }
        );

        static::assertSame($expected, $matcher(...$arguments));
    }

    public function provideMatcherData()
    {
        return [
            [
                ['foo', 'bar'],
                '$a, ...$bb'
            ],
            [
                ['foo', 1],
                'string $string, int $integer'
            ],
            [
                ['foo', 1, new \ArrayIterator()],
                'string $string, int $integer, Traversable $traversable'
            ],
            [
                ['foo', [], new \ArrayObject()],
                'string $string, $a, Traversable $traversable'
            ],
            [
                ['foo'],
                'string $string'
            ],
            [
                [10],
                'int $integer'
            ],
            [
                [[]],
                '$a'
            ],
            [
                [[], []],
                '$a, ...$bb'
            ],
            [
                [[], 1, []],
                '$a, $b, array...$cc'
            ],
            [
                [[], [], 1],
                '$a, $b, ...$cc'
            ],
            [
                [[], [], 1, []],
                '$a, $b, ...$cc'
            ],
            [
                [new \ArrayObject()],
                'ArrayAccess $array_access'
            ],
            [
                ['test', [], []],
                'string $string, $a, $b'
            ],
            [
                [],
                '...$aa'
            ],
        ];
    }

    public function testDefaultTookIntoAccount()
    {
        $matcher = Matcher::for (
            function (int $foo, string $bar, array $baz = []) {
                return 'Yes';
            }
        );

        static::assertSame('Yes', $matcher(1, 'foo'));
        static::assertSame('Yes', $matcher(1, 'foo', ['third']));
    }

    public function testExactWinOverDefault()
    {
        $matcher = Matcher::for (
            function (int $foo, string $bar, array $baz = []) {
                return 'A';
            },
            function (int $foo, string $bar) {
                return 'B';
            }
        );

        static::assertSame('B', $matcher(1, 'foo'));
        static::assertSame('A', $matcher(1, 'foo', ['third']));
    }

    public function testTypedCatchAll()
    {
        $matcher = Matcher::for (
            function (int...$test) {
                return 'A';
            }
        )->failWith(function () {
            return 'B';
        });

        static::assertSame('A', $matcher(1));
        static::assertSame('B', $matcher(1, 'foo'));
        static::assertSame('A', $matcher(1, 2, 3, 4));
    }

    public function testVariadicRespectTypes()
    {
        $matcher = Matcher::for (
            function (string $a, int...$bb) {
                return 'A';
            },
            function ($c, int...$dd) {
                return 'B';
            },
            function (array $e, int...$ff) {
                return 'C';
            }
        );

        static::assertSame('A', $matcher('foo', 1));
        static::assertSame('A', $matcher('foo', 1, 2));
        static::assertSame('B', $matcher(1, 2));
        static::assertSame('B', $matcher(1, 2, 3));
        static::assertSame('B', $matcher(new \stdClass()));
        static::assertSame('C', $matcher([], 2, 3));
    }

    public function testFailWith()
    {
        $matcher = Matcher::for (
            function (int $foo) {
            }
        )->failWith(function () {
            return 'Failed';
        });

        static::assertSame('Failed', $matcher(1, 'foo'));
        static::assertSame('Failed', $matcher());
    }

    public function testFailWithNoCallbacks()
    {
        $matcher = Matcher::for ()->failWith(function () {
            return 'Failed';
        });

        static::assertSame('Failed', $matcher('foo'));
        static::assertSame('Failed', $matcher());
    }

    public function testSpecificity()
    {
        $matcher = Matcher::for (
            function ($a, $b) {
                return 'A';
            },
            function (int $a, $b, ...$c) {
                return 'B';
            }
        );

        static::assertSame('B', $matcher(1, 2));
    }

    public function testMatchThrowExceptionIfNoMatch()
    {
        $matcher = Matcher::for (function (int $arg) {
            return $arg;
        });

        static::expectException(NotMatched::class);

        $matcher('foo');
    }

    public function testNoMatchExceptionIsATypeError()
    {
        $matcher = Matcher::for (function (int $arg) {
            return $arg;
        });

        static::expectException(\TypeError::class);

        $matcher('foo');
    }

    public function testMatchThrownExceptionPassArguments()
    {
        $matcher = Matcher::for (function (int $arg) {
            return $arg;
        });

        try {
            $matcher('foo', 'bar', 1);
        } catch (NotMatched $e) {
            static::assertSame(['foo', 'bar', 1], $e->offendingArgs());
        }

    }

    public function testMatchForInvokableObject()
    {
        $invokable = new class
        {
            public function __invoke(bool $arg)
            {
                return $arg;
            }
        };

        $matcher = Matcher::for ($invokable)->failWith(function () {
            return 'Hi!';
        });

        static::assertTrue($matcher(true));
        static::assertFalse($matcher(false));
        static::assertSame('Hi!', $matcher('foo'));
    }

    public function testMatchForCoreFunction()
    {
        $matcher = Matcher::for (
            function (string $foo, int $bar) {

            },
            function (...$foo) {

            },
            'strtoupper'
        );

        static::assertSame('FOO', $matcher('foo'));
    }

    public function testBindToPlainObj()
    {
        $object = new \stdClass();

        $matcher = Matcher::for (
            function (string $slug, int $id) {

                /** @var \stdClass $this */
                $this->slug = $slug;
                $this->id = $id;

                return $this;
            }
        );

        $matcher = $matcher->bindTo($object);

        /** @var \stdClass $result */
        $result = $matcher('x', 1);

        static::assertSame($object, $result);
        static::assertSame('x', $result->slug);
        static::assertSame(1, $result->id);
    }

    public function testBindCoreObject()
    {
        $matcher = Matcher::for (
            function (string $param) {
                /** @var \ArrayObject $this */
                return $this->offsetExists($param) ? $this[$param] : null;
            },
            function (int $param) {
                /** @var \ArrayObject $this */
                $values = array_values($this->getArrayCopy());
                return array_key_exists($param, $values) ? $values[$param] : null;
            },
            function (string ...$params) {
                return array_map(function ($param) {
                    /** @var \ArrayObject $this */
                    return $this->offsetExists($param) ? $this[$param] : null;
                }, $params);
            }
        );

        $matcher = $matcher->bindTo(new \ArrayObject(['foo' => 'Foo!', 'bar' => 'Bar!']));

        static::assertSame('Foo!', $matcher('foo'));
        static::assertSame('Bar!', $matcher(1));
        static::assertSame(["Foo!", null, "Bar!"], $matcher('foo', 'meh', 'bar'));
    }

    public function testBindTo()
    {
        $object = new \ArrayObject(['foo' => 'bar']);

        $matcher = Matcher::for (
            function (string $slug, int $id) {

                /** @var \ArrayObject $this */
                $this[$slug] = $id;

                return $this->getArrayCopy();
            },
            function (...$args) {
                return $this;
            }
        );

        $matcher = $matcher->bindTo($object);

        static::assertSame(['foo' => 'bar', 'x' => 1], $matcher('x', 1));
        static::assertSame($object, $matcher(1, 'x'));
    }

    public function testBindToWithStaticClosure()
    {
        $object = new \ArrayObject(['foo' => 'bar']);

        $matcher = Matcher::for (
            function (string $slug, int $id) {

                /** @var \ArrayObject $this */
                $this[$slug] = $id;

                return $this->getArrayCopy();
            },
            static function (...$args) {
                return $args;
            }
        );

        $matcher = $matcher->bindTo($object);

        static::assertSame(['foo' => 'bar', 'x' => 1], $matcher('x', 1));
        static::assertSame([1, 'x'], $matcher(1, 'x'));
    }

    public function testBindToThrowIfNonObjectGiven()
    {

        $matcher = Matcher::for (
            function (...$args) {
                return $args;
            }
        );

        $done = true;

        try {
            $matcher->bindTo('foo');
        } catch (InvalidBindTarget $e) {
            $done = false;
            static::assertSame('foo', $e->offendingValue());
        }

        static::assertFalse($done);
    }

    public function testBindPlainFunction()
    {

        $matcher = Matcher::for ('strtoupper');
        $matcher->bindTo(new \ArrayObject());

        static::assertSame('FOO', $matcher('foo'));
    }

}
