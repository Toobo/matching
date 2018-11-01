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
final class CallbackData
{

    /**
     * @var int
     */
    private $param_count;

    /**
     * @var \ReflectionParameter[]
     */
    private $parameters;

    /**
     * @var bool
     */
    private $is_variadic;

    /**
     * @var array
     */
    private $weights = [];

    /**
     * @param int $param_count
     * @param \ReflectionParameter[] ...$parameters
     */
    public function __construct(int $param_count, \ReflectionParameter...$parameters)
    {
        $this->parameters = $parameters;
        $this->param_count = $param_count;
    }

    /**
     * @return \ReflectionParameter[]
     */
    public function params(): array
    {
        return $this->parameters;
    }

    /**
     * @return int
     */
    public function valid_params_count(): int
    {
        return $this->param_count;
    }

    /**
     * Weight is used to distinguish callbacks with same specificity.
     * Callbacks that matched without defaults have higher weight of callbacks that matched
     * skipping one or more optional parameters (the more parameters skipped, the less the weight).
     * Variadic callbacks have lower weight of non-variadic ones.
     *
     * @param array $args
     * @return int
     */
    public function weight(array $args): int
    {
        $count_args = count($args);

        if (array_key_exists($count_args, $this->weights)) {
            return $this->weights[$count_args];
        }

        $weight = $this->isVariadic() ? 0 : $count_args;
        $diff = count($this->params()) - $count_args;

        if ($diff !== 0) {
            $weight -= abs($diff);
            $diff > 0 and $weight -= $count_args;
        }

        $this->weights[$count_args] = $weight;

        return $weight;
    }

    /**
     * @return bool
     */
    public function isVariadic(): bool
    {
        if (!isset($this->is_variadic)) {
            $last = $this->parameters ? end($this->parameters) : null;
            $this->is_variadic = $last && $last->isVariadic();
        }

        return $this->is_variadic;
    }

}
