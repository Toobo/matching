<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Matching package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Toobo\Matching\Tests\Stubs;

use Toobo\Matching\Matcher;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Matching
 * @license http://opensource.org/licenses/MIT MIT
 */
class MultiConstructor
{

    private static $matcher;

    /**
     * @var int
     */
    private $number;

    /**
     * @var string
     */
    private $string;

    /**
     * @return \Toobo\Matching\Matcher
     */
    public static function matcher(): Matcher
    {
        self::$matcher or self::$matcher = Matcher::for (
            function (string $string) {
                $this->string = $string;
                $this->number = 0;
            },
            function (int $number) {
                $this->string = 'default';
                $this->number = $number;
            },
            function (array $args) {
                $string = ($args['string'] ?? null);
                $number = ($args['number'] ?? null);
                $this->string = is_string($string) ? $string : 'default';
                $this->number = is_int($number) ? $number : 0;
            },
            function(...$stuff) {
                $this->string = 'error';
                $this->number = -1;
            }
        );

        return self::$matcher;
    }

    /**
     * @param array ...$args
     */
    public function __construct(...$args)
    {
        (self::matcher()->bindTo($this))(...$args);
    }

    /**
     * @return int
     */
    public function number(): int
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function string(): string
    {
        return $this->string;
    }

}
