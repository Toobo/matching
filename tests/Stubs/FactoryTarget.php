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

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Matching
 * @license http://opensource.org/licenses/MIT MIT
 */
class FactoryTarget
{

    /**
     * @var string[]
     */
    private $things = [];

    /**
     * @param \string[] ...$things
     * @return \Toobo\Matching\Tests\Stubs\FactoryTarget
     */
    public static function fromVariadic(string...$things): FactoryTarget
    {
        $instance = new static();
        $instance->things = $things;

        return $instance;
    }

    /**
     * @param string[] $things
     * @return mixed
     * @throws \Exception
     */
    public static function fromArray(array $things): FactoryTarget
    {
        return self::fromVariadic(...$things);
    }

    /**
     * @param \stdClass $things
     * @return \Toobo\Matching\Tests\Stubs\FactoryTarget
     */
    public static function fromObject(\stdClass $things): FactoryTarget
    {
        return self::fromArray(array_values(get_object_vars($things)));
    }

    /**
     * @param string $thing
     * @return \Toobo\Matching\Tests\Stubs\FactoryTarget
     */
    public static function fromString(string $thing): FactoryTarget
    {
        return self::fromArray([$thing]);
    }

    private function __construct()
    {

    }

    /**
     * @return string[]
     */
    public function things(): array
    {
        return $this->things;
    }

}
