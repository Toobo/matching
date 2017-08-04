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
final class SpecificityCalculator
{

    /**
     * @var \Toobo\Matching\ArgumentMatcher
     */
    private $matcher;

    /**
     * @param \Toobo\Matching\ArgumentMatcher $matcher
     */
    public function __construct(ArgumentMatcher $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * Given data for a callback and a set of arguments return the "specificity" that is a
     * positive number when the arguments match callback data.
     * The specificity is as higher as more type constraint are present in the callback params.
     *
     * @param \Toobo\Matching\CallbackData $data
     * @param array $args
     * @return int
     */
    public function calculate(CallbackData $data, array $args): int
    {
        list($valid, $variadic, $params, $variadic_index) = $this->checkData($data, $args);

        if (!$valid || !$args) {
            return $valid ? 1 : 0;
        }

        $args = array_values($args);

        $specificity = 1;

        foreach ($args as $index => $arg) {

            if (!isset($params[$index]) && !$variadic) {
                return 0;
            }

            $param = $this->param($params, $index, $variadic_index);

            $specificity = $this->specificity($param, $arg, $specificity);

            if (!$specificity) {
                return 0;
            }
        }

        return $specificity;
    }

    /**
     * @param \Toobo\Matching\CallbackData $data
     * @param array $args
     * @return array
     */
    private function checkData(CallbackData $data, array $args): array
    {
        $args_count = count($args);
        $all_params = $data->params();

        /** @var \ReflectionParameter[] $params */
        $params = array_slice($all_params, 0, $data->valid_params_count());
        $params_count = count($params);

        $variadic =
            $data->isVariadic()
            && $args_count > $data->valid_params_count()
            && $all_params === $params;

        $valid = $variadic || $data->valid_params_count() === $args_count;

        return [$valid, $variadic, $params, $params_count - 1];
    }

    /**
     * @param \ReflectionParameter[] $params
     * @param int $index
     * @param int $variadic_i
     * @return \ReflectionParameter
     */
    private function param(array $params, int $index, int $variadic_i): \ReflectionParameter
    {
        $param_index = $index;
        if (!isset($params[$index]) && $params[$variadic_i]->isVariadic()) {
            $param_index = $variadic_i;
        }

        return $params[$param_index];
    }

    /**
     * @param \ReflectionParameter $param
     * @param $value
     * @param int $specificity
     * @return int
     */
    private function specificity(\ReflectionParameter $param, $value, int $specificity): int
    {
        if (!$param->hasType()) {
            return $specificity;
        }

        $is_match = is_object($value)
            ? $this->matcher->matchParamForObject($value, $param)
            : $this->matcher->matchParamForNonObject($value, $param);

        return $is_match ? $specificity + 1 : 0;
    }

}