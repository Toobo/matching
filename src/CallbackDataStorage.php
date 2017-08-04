<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Matching package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Toobo\Matching;

/**
 * A storage for callbacks and callbacks data, like parameters and number of arguments.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Matching
 * @license http://opensource.org/licenses/MIT MIT
 */
final class CallbackDataStorage implements \Countable, \IteratorAggregate
{
    /**
     * @var callable[]
     */
    private $callbacks;

    /**
     * @var \SplObjectStorage
     */
    private $storage;

    /**
     * @var bool
     */
    private $parsed = false;

    /**
     * @param \callable[] ...$callbacks
     */
    public function __construct(callable...$callbacks)
    {
        $this->callbacks or $this->callbacks = $callbacks;
        $this->storage or $this->storage = new \SplObjectStorage();
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        $this->parse();

        return $this->storage->count();
    }

    /**
     * @return \SplObjectStorage
     */
    public function getIterator()
    {
        $this->parse();
        $this->storage->rewind();

        return $this->storage;
    }

    /**
     * Parse callbacks if not done already.
     */
    private function parse()
    {
        if (!$this->parsed) {
            $this->parsed = true;
            array_walk($this->callbacks, [$this, 'parseCallback']);
            $this->callbacks = [];
        }
    }

    /**
     * Takes an added callback, parses its arguments to prepare matching.
     *
     * @param callable $callback
     */
    private function parseCallback(callable $callback)
    {

        list($reflection, $closure) = $this->reflectionAndClosureOf($callback);

        $params = $reflection->getParameters();

        $param_count = $optional_count = 0;

        /** @var \ReflectionParameter $param */
        foreach ($params as $param) {
            $param_count++;

            if ($param->isOptional() || $param->isVariadic()) {
                $optional_count++;
            } elseif ($optional_count > 0) {
                // required args make any previous optional args to be also considered required.
                $optional_count = 0;
            }
        }

        $this->storage->attach($closure, new CallbackData($param_count, ...$params));

        while ($optional_count > 0) {
            $closure = clone $closure;
            $new_count = $param_count - $optional_count;
            $this->storage->attach($closure, new CallbackData($new_count, ...$params));
            $optional_count--;
        }
    }

    /**
     * @param callable $callback
     * @return array
     */
    private function reflectionAndClosureOf(callable $callback): array
    {
        $is_closure = $callback instanceof \Closure;

        if (is_object($callback) && !$is_closure) {
            $callback = [$callback, '__invoke'];
        }

        $is_array = is_array($callback);

        $reflection = $is_array
            ? new \ReflectionMethod($callback[0], $callback[1])
            : new \ReflectionFunction($callback);

        if ($is_closure) {
            return [$reflection, $callback];
        }

        if ($is_array) {
            return [$reflection, $reflection->getClosure($callback[0])];
        }

        return [$reflection, $reflection->getClosure()];
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