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
final class ArgumentMatcher
{

    /**
     * Check if given object argument is of the type of given parameter.
     *
     * Return true also if the parameter has no type at all.
     *
     * @param mixed $value
     * @param \ReflectionParameter $param
     * @return bool
     */
    public function matchParamForObject($value, \ReflectionParameter $param): bool
    {
        if (!is_object($value)) {
            return false;
        }

        if (!$param->hasType()) {
            return true;
        }

        $class = $param->getClass();
        if (!$class) {
            return false;
        }

        return $this->matchTypeForObject($value, $class->getName());
    }

    /**
     * Check if given non-object argument is of the type of given parameter.
     *
     * Return true also if the parameter has no type at all.
     *
     * @param mixed $value
     * @param \ReflectionParameter $param
     * @return bool
     */
    public function matchParamForNonObject($value, \ReflectionParameter $param): bool
    {
        if (is_object($value)) {
            return false;
        }

        if (!$param->hasType()) {
            return true;
        }

        return $this->matchTypeForNonObject($value, (string) $param->getType());
    }

    /**
     * Check if given object argument is of the given type.
     *
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    public function matchTypeForObject($value, string $type): bool
    {
        if (!is_object($value)) {
            return false;
        }

        return $type && (is_a($value, $type) || is_subclass_of($value, $type));
    }

    /**
     * Check if given non-object argument is of the given type.
     *
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    public function matchTypeForNonObject($value, string $type): bool
    {
        if (is_object($value)) {
            return false;
        }

        $arg_type = gettype($value);

        switch ($arg_type) {
            case 'boolean' :
                return $type === 'bool';
            case 'integer':
                return $type === 'int';
            case 'double':
                return $type === 'float';
            case 'string':
            case 'array':
            case 'resource':
                return $type === $arg_type;
        }

        return false;
    }

    /**
     * Check if the given set of arguments are all of the given type.
     *
     * @param array $args
     * @param string|null $type
     * @return bool
     */
    public function isSameType(array $args, string $type): bool
    {
        $for_object = class_exists($type) || interface_exists($type);

        foreach ($args as $arg) {

            if ($for_object && !$this->matchTypeForObject($arg, $type)) {
                return false;
            }

            if (!$for_object && !$this->matchTypeForNonObject($arg, $type)) {
                return false;
            }
        }

        return true;
    }

}
