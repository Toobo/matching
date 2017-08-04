<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Toobo Matching package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Toobo\Matching;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Toobo\Matching
 * @license http://opensource.org/licenses/MIT MIT
 */
final class Matcher
{

    /**
     * @var \Toobo\Matching\CallbackDataStorage
     */
    private $storage;

    /**
     * @var \Toobo\Matching\ArgumentMatcher
     */
    private $matcher;

    /**
     * @var callable
     */
    private $fail_handler;

    /**
     * @var object
     */
    private $bound;

    /**
     * @param callable[] ...$callbacks
     * @return \Toobo\Matching\Matcher
     */
    public static function for (callable...$callbacks): Matcher
    {
        return new static($callbacks);
    }

    /**
     * Private constructor to enforce usage of the static constructor.
     *
     * @param callable[] $callbacks
     */
    private function __construct(array $callbacks)
    {
        $this->storage or $this->storage = new CallbackDataStorage(...$callbacks);
    }

    public function __sleep()
    {
        throw new \BadMethodCallException(sprintf('%s can\'t be serialized.', __CLASS__));
    }

    public function __wakeup()
    {
        throw new \BadMethodCallException(sprintf('%s can\'t be unserialized.', __CLASS__));
    }

    /**
     * Stores a callback to be used when matching fails.
     *
     * @param callable $callback
     *
     * @return callable
     */
    public function failWith(callable $callback): callable
    {
        $this->fail_handler = $callback;

        return $this;
    }

    /**
     * Bind inner callback storage to given object.
     *
     * @param object $object
     * @return callable
     * @throws \Toobo\Matching\Exception\InvalidBindTarget
     */
    public function bindTo($object): callable
    {
        if (!is_object($object)) {
            throw Exception\InvalidBindTarget::forValue($object);
        }
        
        $this->bound = $object;

        return $this;
    }

    /**
     * Search for a matching callback for given arguments, and executes it.
     *
     * @param array ...$args
     *
     * @return mixed
     */
    public function __invoke(...$args)
    {
        $this->matcher or $this->matcher = new CallbackMatcher();

        $fail_handler = $this->fail_handler();
        $callback = $this->matcher->match($args, $this->storage, $fail_handler);

        if ($this->bound && $callback !== $fail_handler && $callback instanceof \Closure) {
            $callback = $this->boundClosure($callback, $this->bound);
        }

        return $callback(...$args);
    }

    /**
     * Return saved fail handler or a callback that throws an exception.
     *
     * @return callable
     */
    private function fail_handler(): callable
    {
        if ($this->fail_handler) {
            return $this->fail_handler;
        }

        return function (...$args) {
            throw Exception\NotMatched::forArgs($args);
        };
    }

    /**
     * @param \Closure $closure
     * @param $object
     * @return \Closure
     */
    private function boundClosure(\Closure $closure, $object): \Closure
    {
        // Quite hackish, but it seems there's no better way to see if a closure can be bound
        $testBound = @\Closure::bind($closure, new \stdClass);
        if (
            $testBound === null
            || (new \ReflectionFunction($testBound))->getClosureThis() === null
        ) {
            return $closure;
        }

        return (new \ReflectionClass($object))->isUserDefined()
            ? \Closure::bind($closure, $object, get_class($object))
            : \Closure::bind($closure, $object);
    }
}