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
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Matching
 * @license http://opensource.org/licenses/MIT MIT
 */
final class CallbackMatcher
{

    /**
     * @var \Toobo\Matching\SpecificityCalculator
     */
    private $calculator;

    /**
     * Find the best match (based on specificity and weight) between a set of arguments and the
     * callbacks stored in a given callback storage.
     *
     * Return given fail handler if no match is found.
     *
     * @param array $args
     * @param \Toobo\Matching\CallbackDataStorage $storage
     * @param callable $fail_handler
     * @return callable
     */
    public function match(
        array $args,
        CallbackDataStorage $storage,
        callable $fail_handler
    ): callable {

        // When no callbacks are there, fail.
        if (!$storage->count()) {
            return $fail_handler;
        }

        $this->calculator or $this->calculator = new SpecificityCalculator(new ArgumentMatcher());

        /** @var callable[] $matched */
        $matched = [];
        $iterator = $storage->getIterator();

        while ($iterator->valid()) {

            list($specificity, $weight) = $this->matchCallback($iterator->getInfo(), $args);

            if ($specificity && !isset($matched[$specificity][$weight])) {
                array_key_exists($specificity, $matched) or $matched[$specificity] = [];
                $matched[$specificity][$weight] = $iterator->current();
            }

            $iterator->next();
        }

        return $this->matched($matched, $fail_handler);
    }

    /**
     * Calculate specificity and weight of given callback data for given array.
     *
     * @param \Toobo\Matching\CallbackData $data
     * @param array $args
     * @return int[]
     */
    private function matchCallback(CallbackData $data, array $args): array
    {

        $specificity = $this->calculator->calculate($data, $args);
        if ($specificity <= 0) {
            return [0, 0];
        }

        return [$specificity, $data->weight($args)];
    }

    /**
     * Return the best match (based on specificity and weight) among the array of given
     * callbacks.
     * Return the given fail handler if there was no match at all.
     *
     * @param array $matched
     * @param callable $fail_handler
     * @return callable
     */
    private function matched(array $matched, callable $fail_handler): callable
    {
        // If matches found...
        if ($matched) {

            // ...let's order by key (specificity)...
            ksort($matched);

            // ...and take the last (higher specificity)
            $matches = end($matched);

            // ...then order by weight
            ksort($matches);

            // ...and take the last (higher weight)
            return end($matches);
        }

        return $fail_handler;
    }

}