<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Toobo Matching package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Toobo\Matching\Exception;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Toobo\Matching
 * @license http://opensource.org/licenses/MIT MIT
 */
class InvalidBindTarget extends Exception
{

    /**
     * @var mixed
     */
    private $value;

    /**
     * @param mixed $value
     * @return InvalidBindTarget
     */
    public static function forValue($value): InvalidBindTarget
    {
        $instance = new static(
            sprintf('Only objects can be bound to callbacks; "%s" given.', gettype($value))
        );
        $instance->value = $value;

        return $instance;
    }

    public function offendingValue()
    {
        return $this->value;
    }

}
