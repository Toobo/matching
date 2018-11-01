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
use Toobo\Matching\ArgumentMatcher;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Matching
 * @license http://opensource.org/licenses/MIT MIT
 */
class ArgumentMatcherTest extends TestCase
{

    public function testMatchParamForObjectReturnFalseIfValueNotObject()
    {
        $matcher = new ArgumentMatcher();

        /** @var \ReflectionParameter|\PHPUnit_Framework_MockObject_MockObject $param */
        $param = $this->createMock(\ReflectionParameter::class);

        static::assertFalse($matcher->matchParamForObject('foo', $param));
    }

    public function testMatchParamForObjectReturnTrueIfParamNoType()
    {
        $matcher = new ArgumentMatcher();

        /** @var \ReflectionParameter|\PHPUnit_Framework_MockObject_MockObject $param */
        $param = $this->createMock(\ReflectionParameter::class);
        $param->method('hasType')->willReturn(false);

        static::assertTrue($matcher->matchParamForObject(new \stdClass(), $param));
    }

    public function testMatchParamForObjectReturnFalseIfParamHasNoClass()
    {
        $matcher = new ArgumentMatcher();

        /** @var \ReflectionParameter|\PHPUnit_Framework_MockObject_MockObject $param */
        $param = $this->createMock(\ReflectionParameter::class);
        $param->method('hasType')->willReturn(true);
        $param->method('getClass')->willReturn(null);

        static::assertFalse($matcher->matchParamForObject(new \stdClass(), $param));
    }

    public function testMatchParamForObjectReturnTrueIfMatch()
    {
        $matcher = new ArgumentMatcher();

        /** @var \ReflectionParameter|\PHPUnit_Framework_MockObject_MockObject $param */
        $param = $this->createMock(\ReflectionParameter::class);
        $param->method('hasType')->willReturn(true);

        /** @var \ReflectionClass|\PHPUnit_Framework_MockObject_MockObject $class */
        $class = $this->createMock(\ReflectionClass::class);
        $class->method('getName')->willReturn('stdClass');

        $param->method('getClass')->willReturn($class);

        static::assertTrue($matcher->matchParamForObject(new \stdClass(), $param));
    }

    public function testMatchParamForNonObjectReturnFalseIfValueIsObject()
    {
        $matcher = new ArgumentMatcher();

        /** @var \ReflectionParameter|\PHPUnit_Framework_MockObject_MockObject $param */
        $param = $this->createMock(\ReflectionParameter::class);

        static::assertFalse($matcher->matchParamForNonObject(new \stdClass(), $param));
    }

    public function testMatchParamForNobObjectReturnTrueIfParamNoType()
    {
        $matcher = new ArgumentMatcher();

        /** @var \ReflectionParameter|\PHPUnit_Framework_MockObject_MockObject $param */
        $param = $this->createMock(\ReflectionParameter::class);
        $param->method('hasType')->willReturn(false);

        static::assertTrue($matcher->matchParamForNonObject('foo', $param));
    }

    public function testMatchParamForNonObjectReturnTrueIfMatch()
    {
        $matcher = new ArgumentMatcher();

        /** @var \ReflectionParameter|\PHPUnit_Framework_MockObject_MockObject $param */
        $param = $this->createMock(\ReflectionParameter::class);
        $param->method('hasType')->willReturn(true);
        $param->method('getType')->willReturn('string');

        static::assertTrue($matcher->matchParamForNonObject('foo', $param));
    }

    public function testMatchTypeForObjectReturnFalseIfValueIsNoObject()
    {
        $matcher = new ArgumentMatcher();

        static::assertFalse($matcher->matchTypeForObject(\stdClass::class, \stdClass::class));
    }

    public function testMatchTypeForObjectReturnTrueIfMatch()
    {
        $matcher = new ArgumentMatcher();

        $value = new \ArrayObject();

        static::assertTrue($matcher->matchTypeForObject($value, \Traversable::class));
        static::assertTrue($matcher->matchTypeForObject($value, \ArrayAccess::class));
        static::assertTrue($matcher->matchTypeForObject($value, \ArrayObject::class));
    }

    public function testMatchTypeForNonObjectReturnFalseIfValueIsObject()
    {
        $matcher = new ArgumentMatcher();

        static::assertFalse($matcher->matchTypeForNonObject(new \stdClass(), \stdClass::class));
    }

    public function testMatchTypeForNonObjectReturnFalseIfValueIsNull()
    {
        $matcher = new ArgumentMatcher();

        static::assertFalse($matcher->matchTypeForNonObject(null, ''));
    }

    public function testMatchTypeForNonObjectReturnTrueIfMatch()
    {
        $matcher = new ArgumentMatcher();

        static::assertTrue($matcher->matchTypeForNonObject('foo', 'string'));
        static::assertTrue($matcher->matchTypeForNonObject(1, 'int'));
        static::assertTrue($matcher->matchTypeForNonObject(1.0, 'float'));
        static::assertTrue($matcher->matchTypeForNonObject([], 'array'));
        static::assertTrue($matcher->matchTypeForNonObject(STDIN, 'resource'));
        static::assertTrue($matcher->matchTypeForNonObject(true, 'bool'));
        static::assertTrue($matcher->matchTypeForNonObject(false, 'bool'));
    }

    public function testIsSameTypeForObject()
    {
        $matcher = new ArgumentMatcher();

        $data = [
            new \stdClass(),
            new \stdClass(),
            new \stdClass(),
        ];

        static::assertTrue($matcher->isSameType($data, \stdClass::class));
        static::assertFalse($matcher->isSameType($data, \ArrayAccess::class));

        $data[] = new \ArrayObject();

        static::assertFalse($matcher->isSameType($data, \stdClass::class));
        static::assertFalse($matcher->isSameType($data, \ArrayAccess::class));
    }

    public function testIsSameTypeForNonObject()
    {
        $matcher = new ArgumentMatcher();

        $data = [
           'foo',
            'bar',
            'baz',
        ];

        static::assertTrue($matcher->isSameType($data, 'string'));
        static::assertFalse($matcher->isSameType($data, 'int'));

        $data[] = 1;

        static::assertFalse($matcher->isSameType($data, 'string'));
        static::assertFalse($matcher->isSameType($data, 'int'));
    }

}
