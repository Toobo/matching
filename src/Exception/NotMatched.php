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
class NotMatched extends Exception
{

    /**
     * @var array
     */
    private $args = [];

    /**
     * @param array $args
     * @return NotMatched
     */
    public static function forArgs(array $args): NotMatched
    {
        $instance = new static('No matching pattern found for given values.');
        $instance->args = $args;

        return $instance;
    }

    public function offendingArgs()
    {
        return $this->args;
    }

}
